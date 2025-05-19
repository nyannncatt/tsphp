<?php
require_once '../config/database.php';
require_once '../includes/messages.php';
$unread_count = getUnreadMessageCount($conn, getUserId());
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    :root {
        --navbar-bg-start: #1e3c72;
        --navbar-bg-end: #2a5298;
        --card-bg: #ffffff;
        --text-color: #2d3748;
        --active-color: #4299e1;
        --hover-bg: #f7fafc;
    }

    .top-navbar {
        background: linear-gradient(135deg, var(--navbar-bg-start), var(--navbar-bg-end));
        padding: 1rem 2rem;
        margin-bottom: 0;
    }

    .nav-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .nav-brand {
        color: white;
        font-size: 1.5rem;
        text-decoration: none;
        font-weight: 600;
    }

    .nav-menu {
        display: flex;
        gap: 1rem;
        align-items: center;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .nav-right {
        margin-left: auto;
    }

    .btn-logout {
        color: rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-logout:hover {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .side-navbar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 250px;
        background: var(--card-bg);
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
        padding: 1rem;
        z-index: 1000;
        display: none;
    }

    .side-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .side-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: var(--text-color);
        text-decoration: none;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        transition: all 0.2s;
    }

    .side-link:hover {
        background: var(--hover-bg);
    }

    .side-link.active {
        background: var(--active-color);
        color: white;
    }

    .mobile-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        padding: 0.5rem;
        cursor: pointer;
    }

    .message-badge {
        background: #ef4444;
        color: white;
        border-radius: 9999px;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        margin-left: 0.5rem;
    }

    @media (max-width: 768px) {
        .mobile-toggle {
            display: block;
        }
        
        .nav-menu {
            display: none;
        }
        
        .side-navbar {
            display: block;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .side-navbar.active {
            transform: translateX(0);
        }
    }
</style>

<!-- Top Navigation -->
<nav class="top-navbar">
    <div class="nav-container d-flex justify-content-between align-items-center">
        <button class="mobile-toggle">
            <i class="bi bi-list fs-4"></i>
        </button>
        
        <a href="dashboard.php" class="nav-brand">
            <i class="bi bi-building me-2"></i>
            SchoolComSphere
        </a>
        
        <ul class="nav-menu">
            <li>
                <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    Dashboard
                </a>
                </li>
            <li>
                <a href="grades.php" class="nav-link <?php echo $current_page == 'grades.php' ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up"></i>
                    My Grades
                </a>
                </li>
            <li>
                <a href="attendance.php" class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check-fill"></i>
                    My Attendance
                </a>
                </li>
            <li>
                <a href="courses.php" class="nav-link <?php echo $current_page == 'courses.php' ? 'active' : ''; ?>">
                    <i class="bi bi-book-fill"></i>
                    My Courses
                </a>
                </li>
            <li>
                <a href="messages.php" class="nav-link <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
                    <i class="bi bi-chat-dots-fill"></i>
                    Messages
                    <?php if ($unread_count > 0): ?>
                    <span class="message-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                </li>
            <li class="nav-right">
                <a href="../auth/logout.php" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
                </li>
            </ul>
        </div>
</nav>

<!-- Mobile Navigation -->
<div class="side-navbar">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="dashboard.php" class="nav-brand">
            <i class="bi bi-building me-2"></i>
            SchoolComSphere
        </a>
        <button class="mobile-toggle">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <ul class="side-menu">
        <li>
            <a href="dashboard.php" class="side-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                Dashboard
            </a>
        </li>
        <li>
            <a href="grades.php" class="side-link <?php echo $current_page == 'grades.php' ? 'active' : ''; ?>">
                <i class="bi bi-graph-up"></i>
                My Grades
            </a>
        </li>
        <li>
            <a href="attendance.php" class="side-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check-fill"></i>
                My Attendance
            </a>
        </li>
        <li>
            <a href="courses.php" class="side-link <?php echo $current_page == 'courses.php' ? 'active' : ''; ?>">
                <i class="bi bi-book-fill"></i>
                My Courses
            </a>
        </li>
        <li>
            <a href="messages.php" class="side-link <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
                <i class="bi bi-chat-dots-fill"></i>
                Messages
                <?php if ($unread_count > 0): ?>
                <span class="message-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="mt-4">
            <a href="../auth/logout.php" class="side-link text-danger">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </li>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggles = document.querySelectorAll('.mobile-toggle');
    const sideNavbar = document.querySelector('.side-navbar');
    
    mobileToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            sideNavbar.classList.toggle('active');
        });
    });
});
</script> 