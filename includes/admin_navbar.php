<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">School Management</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>" 
                       href="students.php">Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'parents.php' ? 'active' : ''; ?>" 
                       href="parents.php">Parents</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'courses.php' ? 'active' : ''; ?>" 
                       href="courses.php">Courses</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'grades.php' ? 'active' : ''; ?>" 
                       href="grades.php">Grades</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>" 
                       href="attendance.php">Attendance</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'announcements.php' ? 'active' : ''; ?>" 
                       href="announcements.php">Announcements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>" 
                       href="messages.php">Messages</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav> 