<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Get user's navigation restrictions
$restriction_query = "SELECT restricted_pages FROM nav_restrictions WHERE user_id = ?";
$restriction_stmt = $conn->prepare($restriction_query);
$restriction_stmt->bind_param("i", $_SESSION['user_id']);
$restriction_stmt->execute();
$restriction_result = $restriction_stmt->get_result();
$restricted_pages = [];
if ($restriction_result->num_rows > 0) {
    $restriction = $restriction_result->fetch_assoc();
    $restricted_pages = explode(',', $restriction['restricted_pages']);
}
?>
<style>
    :root {
        --navbar-bg-start: #242639;
        --navbar-bg-end: #2f3245;
        --card-bg: #ffffff;
        --text-color: #2d3748;
        --text-primary: #ffffff;
        --active-color: #4299e1;
        --hover-bg: #f7fafc;
    }

    .top-navbar {
        background: linear-gradient(135deg, var(--navbar-bg-start), var(--navbar-bg-end));
        padding: 1rem 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .nav-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .nav-brand {
        color: var(--text-primary);
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
        color: var(--text-primary);
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
        color: var(--text-primary);
    }

    .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        color: var(--text-primary);
    }

    .nav-right {
        margin-left: auto;
    }

    .btn-logout {
        color: var(--text-primary);
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
        color: var(--text-primary);
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
        color: var(--text-primary);
        padding: 0.5rem;
        cursor: pointer;
    }

    .mobile-toggle i {
        color: var(--text-primary);
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
        
        <a href="dashboard.php" class="nav-brand d-flex align-items-center me-4">
            <i class="bi bi-building me-2 fs-3"></i>
            <span class="ms-1">SchoolComSphere</span>
        </a>
        
        <ul class="nav-menu ms-4">
            <li>
                <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" onclick="return checkRestriction('dashboard')">
                    <i class="bi bi-grid-1x2-fill"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="students.php" class="nav-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>" onclick="return checkRestriction('students')">
                    <i class="bi bi-mortarboard-fill"></i>
                    Students
                </a>
            </li>
            <li>
                <a href="parents.php" class="nav-link <?php echo $current_page == 'parents.php' ? 'active' : ''; ?>" onclick="return checkRestriction('parents')">
                    <i class="bi bi-people-fill"></i>
                    Parents
                </a>
            </li>
            <li>
                <a href="courses.php" class="nav-link <?php echo $current_page == 'courses.php' ? 'active' : ''; ?>" onclick="return checkRestriction('courses')">
                    <i class="bi bi-book-fill"></i>
                    Courses
                </a>
            </li>
            <li>
                <a href="grades.php" class="nav-link <?php echo $current_page == 'grades.php' ? 'active' : ''; ?>" onclick="return checkRestriction('grades')">
                    <i class="bi bi-graph-up"></i>
                    Grades
                </a>
            </li>
            <li>
                <a href="attendance.php" class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>" onclick="return checkRestriction('attendance')">
                    <i class="bi bi-calendar-check-fill"></i>
                    Attendance
                </a>
            </li>
            <li>
                <a href="announcements.php" class="nav-link <?php echo $current_page == 'announcements.php' ? 'active' : ''; ?>" onclick="return checkRestriction('announcements')">
                    <i class="bi bi-megaphone-fill"></i>
                    Announcements
                </a>
            </li>
            <li>
                <a href="messages.php" class="nav-link <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>" onclick="return checkRestriction('messages')">
                    <i class="bi bi-chat-dots-fill"></i>
                    Messages
                    <?php if (isset($unread_count) && $unread_count > 0): ?>
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
            <a href="dashboard.php" class="side-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" onclick="return checkRestriction('dashboard')">
                <i class="bi bi-grid-1x2-fill"></i>
                Dashboard
            </a>
        </li>
        <li>
            <a href="students.php" class="side-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>" onclick="return checkRestriction('students')">
                <i class="bi bi-mortarboard-fill"></i>
                Students
            </a>
        </li>
        <li>
            <a href="parents.php" class="side-link <?php echo $current_page == 'parents.php' ? 'active' : ''; ?>" onclick="return checkRestriction('parents')">
                <i class="bi bi-people-fill"></i>
                Parents
            </a>
        </li>
        <li>
            <a href="courses.php" class="side-link <?php echo $current_page == 'courses.php' ? 'active' : ''; ?>" onclick="return checkRestriction('courses')">
                <i class="bi bi-book-fill"></i>
                Courses
            </a>
        </li>
        <li>
            <a href="grades.php" class="side-link <?php echo $current_page == 'grades.php' ? 'active' : ''; ?>" onclick="return checkRestriction('grades')">
                <i class="bi bi-graph-up"></i>
                Grades
            </a>
        </li>
        <li>
            <a href="attendance.php" class="side-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>" onclick="return checkRestriction('attendance')">
                <i class="bi bi-calendar-check-fill"></i>
                Attendance
            </a>
        </li>
        <li>
            <a href="announcements.php" class="side-link <?php echo $current_page == 'announcements.php' ? 'active' : ''; ?>" onclick="return checkRestriction('announcements')">
                <i class="bi bi-megaphone-fill"></i>
                Announcements
            </a>
        </li>
        <li>
            <a href="messages.php" class="side-link <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>" onclick="return checkRestriction('messages')">
                <i class="bi bi-chat-dots-fill"></i>
                Messages
                <?php if (isset($unread_count) && $unread_count > 0): ?>
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

<!-- Restriction Warning Modal -->
<div class="modal fade" id="restrictionWarningModal" tabindex="-1" aria-labelledby="restrictionWarningModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-black" id="restrictionWarningModalLabel">Access Restricted</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock text-danger" style="font-size: 3rem;"></i>
                </div>
                <p class="text-black">You do not have permission to access this page. This restriction has been set by an administrator.</p>
                <p class="text-black">If you believe this is an error, please contact your administrator.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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

// Store restricted pages in JavaScript
const restrictedPages = <?php echo json_encode($restricted_pages); ?>;

function checkRestriction(page) {
    if (restrictedPages.includes(page)) {
        // Show warning modal
        const warningModal = new bootstrap.Modal(document.getElementById('restrictionWarningModal'));
        warningModal.show();
        return false; // Prevent navigation
    }
    return true; // Allow navigation
}
</script> 