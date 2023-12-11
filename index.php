<?php
require_once 'db.php';

// Create a PDO instance
$pdo = connectDB();

// Function to retrieve tasks from the database
function getTasks($pdo) {
    $stmt = $pdo->prepare('SELECT * FROM tasks');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add a new task to the database
function addTask($pdo, $task) {
    $stmt = $pdo->prepare('INSERT INTO tasks (task, task_number) VALUES (:task, :task_number)');
    $stmt->bindParam(':task', $task, PDO::PARAM_STR);

    // Get the total number of tasks to determine the new task number
    $totalTasks = count(getTasks($pdo)) + 1;
    $stmt->bindParam(':task_number', $totalTasks, PDO::PARAM_INT);

    $stmt->execute();
}


// Function to mark a task as completed
function completeTask($pdo, $taskId) {
    $stmt = $pdo->prepare('UPDATE tasks SET status = 1 WHERE id = :id');
    $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
    $stmt->execute();
}

// Function to mark a task as incomplete
function incompleteTask($pdo, $taskId) {
    $stmt = $pdo->prepare('UPDATE tasks SET status = 0 WHERE id = :id');
    $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
    $stmt->execute();
}

// Function to remove a task from the database
// function removeTask($pdo, $taskId) {
//     $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
//     $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
//     $stmt->execute();
// }

// Function to remove a task from the database
function removeTask($pdo, $taskId) {
    // Get the task number of the task to be removed
    $stmt = $pdo->prepare('SELECT task_number FROM tasks WHERE id = :id');
    $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
    $stmt->execute();
    $removedTask = $stmt->fetch(PDO::FETCH_ASSOC);

    // Remove the task from the database
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
    $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
    $stmt->execute();

    // Update task numbers of remaining tasks
    $stmt = $pdo->prepare('UPDATE tasks SET task_number = task_number - 1 WHERE task_number > :task_number');
    $stmt->bindParam(':task_number', $removedTask['task_number'], PDO::PARAM_INT);
    $stmt->execute();
}



// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['task']) && !empty($_POST['task'])) {
        // Add a new task
        addTask($pdo, $_POST['task']);
    } elseif (isset($_POST['complete']) && !empty($_POST['complete'])) {
        // Mark task as completed
        completeTask($pdo, $_POST['complete']);
    } elseif (isset($_POST['incomplete']) && !empty($_POST['incomplete'])) {
        // Mark task as incomplete
        incompleteTask($pdo, $_POST['incomplete']);
    }
    // Handle task removal
    if (isset($_POST['remove']) && !empty($_POST['remove'])) {
        removeTask($pdo, $_POST['remove']);
    }
}

// Get tasks from the database
$tasks = getTasks($pdo);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        ul { list-style-type: none; }
        li { margin-bottom: 5px; }
        .completed { text-decoration: line-through; color: #888; }
    </style>
</head>
<body>
    <!-- Body content -->
    <h2>Todo App</h2>
    <form method="post">
        <label for="task">Add Task:</label>
        <input type="text" name="task" required>
        <button type="submit">Add</button>
    </form>
    <h3>Tasks:</h3>
    <ul>
        <?php foreach ($tasks as $task): ?>
            <li class="<?= $task['status'] ? 'completed' : ''; ?>">
                Task <?= $task['task_number']; ?>: <?= $task['task']; ?>
                <?php if (!$task['status']): ?>
                    <!-- If task is incomplete, show Complete button -->
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="complete" value="<?= $task['id']; ?>">
                        <button type="submit">Complete</button>
                    </form>
                <?php else: ?>
                    <!-- If task is complete, show Incomplete button -->
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="incomplete" value="<?= $task['id']; ?>">
                        <button type="submit">Incomplete</button>
                    </form>
                <?php endif; ?>
                <!-- Add a Remove button for each task -->
                <form method="post" style="display:inline;">
                    <input type="hidden" name="remove" value="<?= $task['id']; ?>">
                    <button type="submit">Remove</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
