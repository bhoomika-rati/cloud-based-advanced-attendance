const taskInput = document.getElementById("taskInput");
const addBtn = document.getElementById("addBtn");
const taskList = document.getElementById("taskList");

const totalTasks = document.getElementById("totalTasks");
const completedTasks = document.getElementById("completedTasks");
const progressText = document.getElementById("progressText");

const progressBar = document.getElementById("progressBar");
const circlePercentage = document.getElementById("circlePercentage");
const progressMessage = document.getElementById("progressMessage");

const themeBtn = document.getElementById("themeBtn");
const helloBtn = document.getElementById("helloBtn");
const taskMessage = document.getElementById("taskMessage");


// Initial tasks
let tasks = [
    {
        name: "Create AWS account",
        completed: true
    },
    {
        name: "Deploy website",
        completed: false
    },
    {
        name: "Configure S3 bucket",
        completed: false
    }
];


// Display tasks
function renderTasks() {

    taskList.innerHTML = "";

    tasks.forEach((task, index) => {

        const li = document.createElement("li");

        li.className = "task";

        if (task.completed) {
            li.classList.add("completed");
        }

        li.innerHTML = `
            <input
                type="checkbox"
                ${task.completed ? "checked" : ""}
            >

            <span class="task-name">
                ${task.name}
            </span>

            <button class="delete-btn">
                ✕
            </button>
        `;


        // Complete task
        const checkbox = li.querySelector("input");

        checkbox.addEventListener("change", () => {

            tasks[index].completed = checkbox.checked;

            renderTasks();
            updateStats();

        });


        // Delete task
        const deleteBtn = li.querySelector(".delete-btn");

        deleteBtn.addEventListener("click", () => {

            tasks.splice(index, 1);

            renderTasks();
            updateStats();

        });


        taskList.appendChild(li);

    });

}


// Add task
function addTask() {

    const taskName = taskInput.value.trim();

    if (taskName === "") {

        taskMessage.textContent = "Enter a task first!";
        taskMessage.style.color = "#ef4444";

        return;
    }


    tasks.push({
        name: taskName,
        completed: false
    });


    taskInput.value = "";

    taskMessage.textContent = "Task added!";
    taskMessage.style.color = "#16a34a";


    renderTasks();
    updateStats();

    setTimeout(() => {
        taskMessage.textContent = "";
    }, 1500);
}


// Update statistics
function updateStats() {

    const total = tasks.length;

    const completed = tasks.filter(
        task => task.completed
    ).length;


    const percentage =
        total === 0
            ? 0
            : Math.round((completed / total) * 100);


    totalTasks.textContent = total;

    completedTasks.textContent = completed;

    progressText.textContent = `${percentage}%`;

    circlePercentage.textContent = `${percentage}%`;

    progressBar.style.width = `${percentage}%`;


    if (percentage === 100 && total > 0) {

        progressMessage.textContent =
            "🎉 Great job! All tasks completed!";

    } else if (percentage >= 50) {

        progressMessage.textContent =
            "🔥 You're doing great!";

    } else if (percentage > 0) {

        progressMessage.textContent =
            "💪 Keep going!";

    } else {

        progressMessage.textContent =
            "Start completing your tasks!";

    }

}


// Add task button
addBtn.addEventListener("click", addTask);


// Press Enter to add task
taskInput.addEventListener("keydown", (event) => {

    if (event.key === "Enter") {
        addTask();
    }

});


// Dark mode
themeBtn.addEventListener("click", () => {

    document.body.classList.toggle("dark");

    if (document.body.classList.contains("dark")) {

        themeBtn.textContent = "☀️ Light Mode";

    } else {

        themeBtn.textContent = "🌙 Dark Mode";

    }

});


// Demo button
helloBtn.addEventListener("click", () => {

    alert(
        "🚀 Hello from your AWS Demo!\n\n" +
        "This page is running using HTML, CSS and JavaScript."
    );

});


// Initial render
renderTasks();
updateStats();
