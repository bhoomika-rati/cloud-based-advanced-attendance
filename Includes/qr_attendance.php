<?php

function qrAttendanceTimezone(): DateTimeZone
{
    static $timezone;
    if (!$timezone) {
        $timezone = new DateTimeZone('Asia/Kolkata');
    }

    return $timezone;
}

function qrAttendanceNow(): DateTimeImmutable
{
    return new DateTimeImmutable('now', qrAttendanceTimezone());
}

function qrAttendanceSqlTime(DateTimeInterface $time): string
{
    return (new DateTimeImmutable($time->format('Y-m-d H:i:s'), $time->getTimezone()))
        ->setTimezone(qrAttendanceTimezone())
        ->format('Y-m-d H:i:s');
}

function qrAttendanceTokenKey(): string
{
    $encodedKey = trim((string)getenv('QR_ATTENDANCE_TOKEN_KEY'));
    $key = base64_decode($encodedKey, true);
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException(
            'Set QR_ATTENDANCE_TOKEN_KEY to a base64-encoded random 32-byte secret on the PHP server.'
        );
    }

    return $key;
}

function qrAttendanceSessionToken(int $sessionId): string
{
    if ($sessionId <= 0) {
        throw new InvalidArgumentException('A valid session is required to create an attendance token.');
    }

    return hash_hmac(
        'sha256',
        'qr-attendance-session:' . $sessionId,
        qrAttendanceTokenKey()
    );
}

function qrAttendanceLocationSettings(mysqli $conn, bool $lockForUpdate = false): array
{
    $sql = 'SELECT latitude, longitude, radius_m FROM qr_attendance_settings WHERE id = 1'
        . ($lockForUpdate ? ' FOR UPDATE' : '');
    $result = $conn->query($sql);
    if (!$result) {
        throw new RuntimeException('Unable to load the college attendance location settings.');
    }

    $settings = $result->fetch_assoc();
    if (!$settings) {
        throw new RuntimeException('The college attendance location has not been configured by an administrator.');
    }
    if ($settings['latitude'] === null || $settings['longitude'] === null || $settings['radius_m'] === null) {
        throw new RuntimeException('The college attendance location and radius have not been configured by an administrator.');
    }
    if (!is_numeric($settings['latitude']) || !is_numeric($settings['longitude'])
        || (float)$settings['latitude'] < -90 || (float)$settings['latitude'] > 90
        || (float)$settings['longitude'] < -180 || (float)$settings['longitude'] > 180
        || (int)$settings['radius_m'] < 1 || (int)$settings['radius_m'] > 10000) {
        throw new RuntimeException('The college attendance location settings are invalid.');
    }

    return [
        'latitude' => (float)$settings['latitude'],
        'longitude' => (float)$settings['longitude'],
        'radius_m' => (int)$settings['radius_m']
    ];
}

function qrAttendanceDistanceMeters(
    float $latitude,
    float $longitude,
    float $collegeLatitude,
    float $collegeLongitude
): float {
    $earthRadiusMeters = 6371000.0;
    $latitudeDelta = deg2rad($collegeLatitude - $latitude);
    $longitudeDelta = deg2rad($collegeLongitude - $longitude);
    $a = sin($latitudeDelta / 2) ** 2
        + cos(deg2rad($latitude)) * cos(deg2rad($collegeLatitude))
        * sin($longitudeDelta / 2) ** 2;

    return 2 * $earthRadiusMeters * asin(min(1.0, sqrt($a)));
}

function qrFinalizeSessionLocked(
    mysqli $conn,
    array $session,
    DateTimeImmutable $endedAt,
    string $finalStatus = 'expired'
): void
{
    if (!in_array($finalStatus, ['expired', 'closed'], true)) {
        throw new InvalidArgumentException('Unsupported QR attendance final status.');
    }

    $sessionId = (int)$session['id'];
    $endedAtSql = qrAttendanceSqlTime($endedAt);

    $expireStmt = $conn->prepare(
        "UPDATE qr_attendance_sessions
         SET status = ?, expired_at = ?, closed_at = ?
         WHERE id = ? AND status = 'active'"
    );
    if (!$expireStmt) {
        throw new RuntimeException('Unable to prepare QR session expiry.');
    }
    $expiredAtSql = $finalStatus === 'expired' ? $endedAtSql : null;
    $closedAtSql = $finalStatus === 'closed' ? $endedAtSql : null;
    $expireStmt->bind_param('sssi', $finalStatus, $expiredAtSql, $closedAtSql, $sessionId);
    if (!$expireStmt->execute()) {
        $error = $expireStmt->error;
        $expireStmt->close();
        throw new RuntimeException('Unable to expire QR attendance session: ' . $error);
    }
    $expireStmt->close();

    $absentStmt = $conn->prepare(
        "UPDATE qr_attendance_records
         SET status = 'absent'
         WHERE qr_session_id = ? AND status = 'not_marked'"
    );
    if (!$absentStmt) {
        throw new RuntimeException('Unable to prepare absent roster update.');
    }
    $absentStmt->bind_param('i', $sessionId);
    if (!$absentStmt->execute()) {
        $error = $absentStmt->error;
        $absentStmt->close();
        throw new RuntimeException('Unable to finalize absent students: ' . $error);
    }
    $absentStmt->close();

    $legacyStmt = $conn->prepare(
        "INSERT INTO tblattendance
            (admissionNo, classId, classArmId, sessionTermId, status, dateTimeTaken, qr_session_id)
         SELECT roster.admission_number, ?, ?, ?, '1', ?, roster.qr_session_id
         FROM qr_attendance_records roster
         WHERE roster.qr_session_id = ?
           AND roster.status = 'absent'
           AND roster.legacy_synced = 0"
    );
    if (!$legacyStmt) {
        throw new RuntimeException('Unable to prepare legacy absent attendance records.');
    }
    $classId = (string)$session['class_id'];
    $classArmId = (string)$session['class_arm_id'];
    $academicSessionId = (string)$session['academic_session_id'];
    $legacyStmt->bind_param(
        'ssssi',
        $classId,
        $classArmId,
        $academicSessionId,
        $endedAtSql,
        $sessionId
    );
    if (!$legacyStmt->execute()) {
        $error = $legacyStmt->error;
        $legacyStmt->close();
        throw new RuntimeException('Unable to save absent attendance records: ' . $error);
    }
    $legacyStmt->close();

    $syncStmt = $conn->prepare(
        "UPDATE qr_attendance_records SET legacy_synced = 1
         WHERE qr_session_id = ? AND status = 'absent'"
    );
    if (!$syncStmt) {
        throw new RuntimeException('Unable to prepare absent attendance sync.');
    }
    $syncStmt->bind_param('i', $sessionId);
    if (!$syncStmt->execute()) {
        $error = $syncStmt->error;
        $syncStmt->close();
        throw new RuntimeException('Unable to sync absent attendance records: ' . $error);
    }
    $syncStmt->close();
}

function qrCloseAttendanceSession(mysqli $conn, int $sessionId, int $teacherId): bool
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "SELECT * FROM qr_attendance_sessions
             WHERE id = ? AND teacher_id = ? FOR UPDATE"
        );
        if (!$stmt) {
            throw new RuntimeException('Unable to prepare QR session close.');
        }
        $stmt->bind_param('ii', $sessionId, $teacherId);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Unable to load QR session for closing: ' . $error);
        }
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$session || $session['status'] !== 'active') {
            $conn->commit();
            return false;
        }

        $now = qrAttendanceNow();
        $expiresAt = new DateTimeImmutable($session['expires_at'], qrAttendanceTimezone());
        if ($now >= $expiresAt) {
            qrFinalizeSessionLocked($conn, $session, $expiresAt, 'expired');
            $conn->commit();
            return false;
        }

        qrFinalizeSessionLocked($conn, $session, $now, 'closed');
        $conn->commit();
        return true;
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}

function qrFinalizeExpiredSession(mysqli $conn, int $sessionId): void
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "SELECT * FROM qr_attendance_sessions WHERE id = ? FOR UPDATE"
        );
        if (!$stmt) {
            throw new RuntimeException('Unable to prepare QR session lookup.');
        }
        $stmt->bind_param('i', $sessionId);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Unable to load QR session: ' . $error);
        }
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($session && $session['status'] === 'active') {
            $now = qrAttendanceNow();
            $expiresAt = new DateTimeImmutable($session['expires_at'], qrAttendanceTimezone());
            if ($now >= $expiresAt) {
                qrFinalizeSessionLocked($conn, $session, $expiresAt);
            }
        }

        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}
