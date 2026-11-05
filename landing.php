<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A smarter way to manage campus attendance with dynamic QR check-in and real-time tracking.">
    <meta name="theme-color" content="#102b3f">
    <title>Attendly | Smart Attendance Management</title>
    <link rel="icon" href="img/logo/attendly-mark.svg" type="image/svg+xml">
    <link rel="stylesheet" href="vendore/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendore/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="css/landing.css">
</head>
<body>
    <a class="landing-skip-link" href="#main-content">Skip to content</a>
    <header class="landing-header">
        <nav class="landing-nav container" aria-label="Main navigation">
            <a class="landing-brand" href="index.php#home" aria-label="Attendly home">
                <img src="img/logo/attendly-mark.svg" alt="">
                <span><strong>Attendly</strong><small>Your Institution Name</small></span>
            </a>
            <button class="landing-menu-toggle" type="button" aria-expanded="false" aria-controls="landing-menu" aria-label="Open navigation menu">
                <span></span><span></span><span></span>
            </button>
            <div class="landing-menu" id="landing-menu">
                <a href="index.php#home">Home</a>
                <a href="#about">About</a>
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#contact">Contact Us</a>
                <a class="landing-nav-login" href="#login">Login <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <section class="landing-hero" id="home">
            <div class="container landing-hero-grid">
                <div class="landing-hero-copy landing-reveal">
                    <span class="landing-eyebrow"><span class="landing-live-dot"></span> A smarter campus starts here</span>
                    <h1>Smart Attendance <span>Management System</span></h1>
                    <p class="landing-hero-lead">A faster, more reliable way to keep every class in sync.</p>
                    <p class="landing-hero-description">Make attendance effortless with dynamic QR check-in, secure records and real-time visibility for administrators, faculty and students.</p>
                    <div class="landing-hero-actions">
                        <a class="landing-button landing-button-primary" href="#login">Get Started <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        <a class="landing-button landing-button-outline" href="#features">Explore Features</a>
                    </div>
                    <div class="landing-hero-proof">
                        <span><i class="fas fa-shield-alt" aria-hidden="true"></i> Secure records</span>
                        <span><i class="fas fa-bolt" aria-hidden="true"></i> Quick check-in</span>
                        <span><i class="fas fa-chart-line" aria-hidden="true"></i> Live insights</span>
                    </div>
                </div>
                <div class="landing-hero-visual landing-reveal" aria-label="Illustration of a student checking in with a QR code">
                    <div class="landing-orbit landing-orbit-one"></div>
                    <div class="landing-orbit landing-orbit-two"></div>
                    <div class="landing-qr-card">
                        <div class="landing-qr-card-head"><span class="landing-qr-badge"><i class="fas fa-qrcode" aria-hidden="true"></i></span><span><strong>Class check-in</strong><small>Dynamic QR session</small></span><i class="fas fa-ellipsis-h landing-qr-more" aria-hidden="true"></i></div>
                        <div class="landing-qr-code" aria-hidden="true">
                            <svg viewBox="0 0 168 168" role="presentation">
                                <rect width="168" height="168" rx="8" fill="#fff"/>
                                <g fill="#102b3f">
                                    <path d="M8 8h48v48H8zm8 8v32h32V16zm8 8h16v16H24zM112 8h48v48h-48zm8 8v32h32V16zm8 8h16v16h-16zM8 112h48v48H8zm8 8v32h32v-32zm8 8h16v16H24z"/>
                                    <path d="M72 8h8v8h-8zm16 0h8v16h-8zm16 8h8v8h-8zM72 32h16v8H72zm24 8h8v16h-8zm-24 8h8v16h-8zm16 8h8v8h-8zm24 8h8v8h-8zm-40 8h8v16h-8zm16 0h16v8H88zm24 0h8v16h-8zm16 0h8v8h-8zm16 0h8v8h-8zM8 72h8v8H8zm16 0h16v8H24zm24 0h16v8H48zm16 8h8v8h-8zm16 0h16v8H80zm24 0h8v8h-8zm16-8h8v16h-8zm16 8h8v8h-8zM8 88h16v8H8zm24 0h8v16h-8zm16 0h8v8h-8zm24 8h8v8h-8zm16 0h8v16h-8zm16 0h16v8h-16zm24 0h8v16h-8zm16 0h8v8h-8zM64 112h8v8h-8zm16 0h16v8H80zm24 8h8v8h-8zm16-8h8v16h-8zm16 8h8v8h-8zm16-8h8v16h-8zM64 128h16v8H64zm24 8h8v16h-8zm16-8h16v8h-16zm24 8h8v8h-8zm16 8h16v8h-16z"/>
                                </g>
                            </svg>
                            <span class="landing-qr-center"><i class="fas fa-check" aria-hidden="true"></i></span>
                        </div>
                        <div class="landing-qr-status"><span><i class="fas fa-circle" aria-hidden="true"></i> Session active</span><span>Expires in 00:28</span></div>
                    </div>
                    <div class="landing-checkin-note"><span class="landing-checkin-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span><span><strong>Attendance marked</strong><small>You're checked in for class</small></span><i class="fas fa-check-circle" aria-hidden="true"></i></div>
                    <div class="landing-visual-caption"><i class="fas fa-lock" aria-hidden="true"></i> Secure. Simple. In real time.</div>
                </div>
            </div>
            <a class="landing-scroll-cue" href="#about"><span></span> Scroll to explore</a>
        </section>

        <section class="landing-section landing-about" id="about">
            <div class="container landing-about-grid">
                <div class="landing-section-intro landing-reveal">
                    <span class="landing-eyebrow landing-eyebrow-dark">About the system</span>
                    <h2>Attendance that moves at the speed of your campus.</h2>
                </div>
                <div class="landing-about-copy landing-reveal">
                    <p>Attendly helps educational institutions replace paper registers and repetitive roll calls with a connected digital workflow. Faculty create a dynamic QR code for a class session, students check in quickly, and attendance is recorded for authorized users to review.</p>
                    <p>From daily attendance history to timetables and reports, each role gets the information it needs in one organized system.</p>
                    <a class="landing-text-link" href="#how-it-works">See how it works <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </section>

        <section class="landing-section landing-features" id="features">
            <div class="container">
                <div class="landing-section-heading landing-reveal">
                    <span class="landing-eyebrow landing-eyebrow-dark">Everything in one place</span>
                    <h2>Tools for a more connected campus</h2>
                    <p>Purpose-built features make everyday attendance easier for your whole institution.</p>
                </div>
                <div class="landing-feature-grid">
                    <article class="landing-feature-card landing-reveal"><span class="landing-feature-icon"><i class="fas fa-qrcode" aria-hidden="true"></i></span><h3>Dynamic QR Generation</h3><p>Create time-limited QR codes for class sessions to support secure check-in.</p></article>
                    <article class="landing-feature-card landing-reveal"><span class="landing-feature-icon feature-teal"><i class="fas fa-camera" aria-hidden="true"></i></span><h3>Quick QR Scanning</h3><p>Let students scan an active class code and submit attendance with ease.</p></article>
                    <article class="landing-feature-card landing-reveal"><span class="landing-feature-icon feature-blue"><i class="fas fa-chart-line" aria-hidden="true"></i></span><h3>Real-Time Tracking</h3><p>See recorded attendance and class participation as it is submitted.</p></article>
                    <article class="landing-feature-card landing-reveal"><span class="landing-feature-icon feature-violet"><i class="fas fa-history" aria-hidden="true"></i></span><h3>Student Attendance History</h3><p>Review attendance records and follow progress over time.</p></article>
                    <article class="landing-feature-card landing-reveal"><span class="landing-feature-icon feature-orange"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></span><h3>Faculty Management</h3><p>Manage assigned classes, student rosters and attendance sessions.</p></article>
                    <article class="landing-feature-card landing-reveal"><span class="landing-feature-icon feature-pink"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span><h3>Student Timetable</h3><p>Keep class schedules and upcoming sessions easy to find.</p></article>
                    <article class="landing-feature-card landing-reveal"><span class="landing-feature-icon feature-navy"><i class="fas fa-sliders-h" aria-hidden="true"></i></span><h3>Admin Dashboard</h3><p>Manage people, classes and academic information from one place.</p></article>
                    <article class="landing-feature-card landing-reveal"><span class="landing-feature-icon feature-gold"><i class="fas fa-file-alt" aria-hidden="true"></i></span><h3>Reports &amp; Analytics</h3><p>Explore attendance summaries and export records for review.</p></article>
                </div>
            </div>
        </section>

        <section class="landing-section landing-process" id="how-it-works">
            <div class="container">
                <div class="landing-section-heading landing-reveal">
                    <span class="landing-eyebrow">Simple by design</span>
                    <h2>Three steps. One smooth check-in.</h2>
                    <p>A clear process from class start to attendance record.</p>
                </div>
                <div class="landing-steps">
                    <article class="landing-step landing-reveal"><span class="landing-step-number">01</span><span class="landing-step-icon"><i class="fas fa-qrcode" aria-hidden="true"></i></span><h3>Faculty creates a code</h3><p>The faculty member starts a session and generates a dynamic QR code.</p></article>
                    <span class="landing-step-connector" aria-hidden="true"><i class="fas fa-long-arrow-alt-right"></i></span>
                    <article class="landing-step landing-reveal"><span class="landing-step-number">02</span><span class="landing-step-icon"><i class="fas fa-mobile-alt" aria-hidden="true"></i></span><h3>Students scan to check in</h3><p>Students scan the active code to submit their attendance securely.</p></article>
                    <span class="landing-step-connector" aria-hidden="true"><i class="fas fa-long-arrow-alt-right"></i></span>
                    <article class="landing-step landing-reveal"><span class="landing-step-number">03</span><span class="landing-step-icon"><i class="fas fa-clipboard-check" aria-hidden="true"></i></span><h3>Records update instantly</h3><p>Attendance is recorded and made available in the system for review.</p></article>
                </div>
            </div>
        </section>

        <section class="landing-section landing-login" id="login">
            <div class="container">
                <div class="landing-section-heading landing-reveal">
                    <span class="landing-eyebrow landing-eyebrow-dark">Welcome to your portal</span>
                    <h2>Choose your sign-in</h2>
                    <p>Continue to the tools and information for your role.</p>
                </div>
                <div class="landing-login-grid">
                    <article class="landing-login-card landing-reveal"><span class="landing-login-icon"><i class="fas fa-user-shield" aria-hidden="true"></i></span><h3>Admin Login</h3><p>Manage your institution, users and attendance records.</p><a href="index.php?role=Administrator" class="landing-login-link">Continue as Admin <i class="fas fa-arrow-right" aria-hidden="true"></i></a></article>
                    <article class="landing-login-card landing-login-card-featured landing-reveal"><span class="landing-login-icon"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></span><h3>Faculty Login</h3><p>Manage class sessions, rosters and attendance check-ins.</p><a href="index.php?role=ClassTeacher" class="landing-login-link">Continue as Faculty <i class="fas fa-arrow-right" aria-hidden="true"></i></a></article>
                    <article class="landing-login-card landing-reveal"><span class="landing-login-icon"><i class="fas fa-user-graduate" aria-hidden="true"></i></span><h3>Student Login</h3><p>View your attendance history, timetable and updates.</p><a href="index.php?role=Student" class="landing-login-link">Continue as Student <i class="fas fa-arrow-right" aria-hidden="true"></i></a></article>
                </div>
            </div>
        </section>

        <section class="landing-benefits" aria-labelledby="benefits-title">
            <div class="container landing-benefits-inner">
                <div class="landing-benefits-heading landing-reveal"><span class="landing-eyebrow">A better everyday workflow</span><h2 id="benefits-title">Less busywork. More time for learning.</h2><p>Make routine attendance easier to manage without losing sight of accurate records.</p><a class="landing-button landing-button-light" href="#login">Explore your portal <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
                <ul class="landing-benefits-list landing-reveal">
                    <li><span><i class="fas fa-clock" aria-hidden="true"></i></span><div><strong>Saves valuable time</strong><small>Spend less class time on manual roll calls.</small></div></li>
                    <li><span><i class="fas fa-tasks" aria-hidden="true"></i></span><div><strong>Reduces manual work</strong><small>Move repetitive attendance tasks into a digital flow.</small></div></li>
                    <li><span><i class="fas fa-bullseye" aria-hidden="true"></i></span><div><strong>Improves accuracy</strong><small>Keep attendance records consistent and organized.</small></div></li>
                    <li><span><i class="fas fa-lock" aria-hidden="true"></i></span><div><strong>Protects attendance records</strong><small>Keep access within the system's user roles.</small></div></li>
                    <li><span><i class="fas fa-eye" aria-hidden="true"></i></span><div><strong>Makes monitoring easier</strong><small>Review attendance history and reports when needed.</small></div></li>
                </ul>
            </div>
        </section>

        <section class="landing-contact" id="contact">
            <div class="container landing-contact-inner">
                <div class="landing-contact-copy landing-reveal"><span class="landing-eyebrow landing-eyebrow-dark">Get in touch</span><h2>We’re here to help your campus move forward.</h2><p>For attendance system questions, contact your institution’s support team.</p></div>
                <div class="landing-contact-details landing-reveal">
                    <div><span class="landing-contact-icon"><i class="fas fa-university" aria-hidden="true"></i></span><span><small>Institution</small><strong>Your Institution Name</strong></span></div>
                    <div><span class="landing-contact-icon"><i class="fas fa-envelope" aria-hidden="true"></i></span><span><small>Email</small><a href="mailto:hello@yourinstitution.edu">hello@yourinstitution.edu</a></span></div>
                    <div><span class="landing-contact-icon"><i class="fas fa-phone-alt" aria-hidden="true"></i></span><span><small>Phone</small><a href="tel:+10000000000">+1 (000) 000-0000</a></span></div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="container">
            <div class="landing-footer-main">
                <a class="landing-brand landing-footer-brand" href="index.php#home"><img src="img/logo/attendly-mark.svg" alt=""><span><strong>Attendly</strong><small>Your Institution Name</small></span></a>
                <p>Smarter attendance, stronger campus connections.</p>
                <div class="landing-footer-links"><a href="index.php#home">Home</a><a href="#about">About</a><a href="#features">Features</a><a href="#how-it-works">How It Works</a><a href="#contact">Contact</a><a href="#login">Login</a></div>
            </div>
            <div class="landing-footer-bottom"><span>&copy; <?= date('Y') ?> Your Institution Name. All rights reserved.</span><span>Powered by Attendly</span></div>
        </div>
    </footer>
    <script src="js/landing.js"></script>
</body>
</html>
