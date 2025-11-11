<?php
/**
 * Role-based styling for MediArchive - Modern UI matching home page aesthetic
 */
$role = $_SESSION['role'] ?? 'patient';
?>
<style>
:root {
    --brand-primary: #0f63d6;
    --brand-dark: #0b3d91;
    --brand-light: #eef5ff;
    --accent-cyan: #1bd6d2;
    --accent-lime: #7bf1a8;
    --text-body: #1f2a44;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Common Styles */
.card {
    border-radius: 18px;
    box-shadow: 0 18px 40px rgba(15, 99, 214, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #dde6ff;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 24px 48px rgba(15, 99, 214, 0.15);
}
.card-header {
    border-radius: 18px 18px 0 0 !important;
    padding: 18px 24px;
    font-weight: 600;
}
.table {
    border-radius: 12px;
    overflow: hidden;
}
.table thead th {
    font-weight: 600;
    padding: 16px;
}
.table tbody tr {
    transition: background 0.2s ease;
}
.form-control, .form-select {
    border-radius: 12px;
    padding: 12px 16px;
    transition: all 0.3s ease;
}
.btn {
    font-weight: 600;
    padding: 12px 28px;
    border-radius: 40px;
    transition: all 0.3s ease;
}
.btn:hover {
    transform: translateY(-2px);
}
.sidebar .nav-link {
    padding: 14px 20px;
    margin: 6px 12px;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-weight: 500;
}
.sidebar .nav-link:hover {
    transform: translateX(4px);
}

<?php if ($role === 'web_admin'): ?>
/* Web Admin - Modern Dark Theme */
body { background: #0a0a0a; color: #e0e0e0; }
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #1a1a1a 0%, #000000 100%);
    border-right: 1px solid #333;
    box-shadow: 2px 0 20px rgba(0,0,0,0.5);
}
.sidebar .nav-link { color: #e0e0e0; }
.sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; }
.sidebar .nav-link.active {
    background: linear-gradient(135deg, rgba(27, 214, 210, 0.2), rgba(123, 241, 168, 0.2));
    color: #fff;
    border-left: 3px solid #1bd6d2;
    box-shadow: 0 4px 12px rgba(27, 214, 210, 0.3);
}
.main-content { padding: 30px; background: #0a0a0a; color: #e0e0e0; }
.card { background: #1a1a1a; border: 1px solid #333; color: #e0e0e0; }
.card-header { background: linear-gradient(135deg, #252525 0%, #1a1a1a 100%); border-bottom: 1px solid #333; color: #fff; }
.stats-card {
    border-left: 4px solid #1bd6d2;
    background: linear-gradient(135deg, #1a1a1a 0%, #252525 100%);
    box-shadow: 0 8px 24px rgba(27, 214, 210, 0.2);
}
.table { color: #e0e0e0; background: #1a1a1a; }
.table thead th { border-bottom: 2px solid #333; color: #1bd6d2; background: #252525; }
.table tbody tr { border-bottom: 1px solid #333; background: #1a1a1a; }
.table tbody tr:hover { background: #252525; }
.btn-primary {
    background: linear-gradient(135deg, #1bd6d2 0%, #7bf1a8 100%);
    border: none;
    color: #000;
    box-shadow: 0 8px 20px rgba(27, 214, 210, 0.3);
}
.btn-primary:hover { box-shadow: 0 12px 28px rgba(27, 214, 210, 0.4); color: #000; }
.form-control, .form-select { background: #1a1a1a; border: 1px solid #333; color: #e0e0e0; }
.form-control:focus, .form-select:focus {
    background: #1a1a1a;
    border-color: #1bd6d2;
    color: #fff;
    box-shadow: 0 0 0 0.2rem rgba(27, 214, 210, 0.25);
}

<?php elseif ($role === 'clinic_admin'): ?>
/* Clinic Admin - Modern Green Theme */
body { background: #f7f9ff; color: var(--text-body); }
.sidebar {
    min-height: 100vh;
    background: linear-gradient(135deg, rgba(46, 125, 50, 0.95), rgba(27, 94, 32, 0.95));
    backdrop-filter: blur(12px);
    box-shadow: 2px 0 30px rgba(46, 125, 50, 0.25);
}
.sidebar .nav-link { color: rgba(255,255,255,0.9); }
.sidebar .nav-link:hover { background: rgba(255,255,255,0.15); color: #fff; }
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.25);
    color: #fff;
    border-left: 3px solid #fff;
    box-shadow: 0 4px 12px rgba(255,255,255,0.2);
}
.main-content { padding: 30px; background: #f7f9ff; }
.card { background: linear-gradient(135deg, #ffffff 0%, #f3f6ff 100%); color: var(--text-body); }
.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e9 100%);
    border-bottom: 1px solid #dde6ff;
    color: #2e7d32;
}
.stats-card { border-left: 4px solid #2e7d32; box-shadow: 0 18px 40px rgba(46, 125, 50, 0.1); }
.table { color: var(--text-body); background: #fff; }
.table thead th { border-bottom: 2px solid #2e7d32; color: #2e7d32; background: #f8f9fa; }
.table tbody tr { border-bottom: 1px solid #e0e0e0; }
.table tbody tr:hover { background: #f0f8f0; }
.btn-primary {
    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
    border: none;
    color: #fff;
    box-shadow: 0 8px 20px rgba(46, 125, 50, 0.3);
}
.btn-primary:hover { box-shadow: 0 12px 28px rgba(46, 125, 50, 0.4); }
.form-control, .form-select { background: #fff; border: 1px solid #dde6ff; color: var(--text-body); }
.form-control:focus, .form-select:focus {
    border-color: #2e7d32;
    box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
}

<?php else: ?>
/* Patient - Modern Blue Theme */
body { background: #f7f9ff; color: var(--text-body); }
.sidebar {
    min-height: 100vh;
    background: linear-gradient(135deg, rgba(15, 99, 214, 0.95), rgba(11, 61, 145, 0.95));
    backdrop-filter: blur(12px);
    box-shadow: 2px 0 30px rgba(11, 61, 145, 0.25);
}
.sidebar .nav-link { color: rgba(255,255,255,0.9); }
.sidebar .nav-link:hover { background: rgba(255,255,255,0.15); color: #fff; }
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.25);
    color: #fff;
    border-left: 3px solid #fff;
    box-shadow: 0 4px 12px rgba(255,255,255,0.2);
}
.main-content { padding: 30px; background: #f7f9ff; }
.card { background: linear-gradient(135deg, #ffffff 0%, #f3f6ff 100%); color: var(--text-body); }
.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
    border-bottom: 1px solid #dde6ff;
    color: var(--brand-primary);
}
.stats-card { border-left: 4px solid var(--brand-primary); box-shadow: 0 18px 40px rgba(15, 99, 214, 0.1); }
.table { color: var(--text-body); background: #fff; }
.table thead th { border-bottom: 2px solid var(--brand-primary); color: var(--brand-primary); background: #f8f9fa; }
.table tbody tr { border-bottom: 1px solid #e0e0e0; }
.table tbody tr:hover { background: #f0f4f8; }
.btn-primary {
    background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-dark) 100%);
    border: none;
    color: #fff;
    box-shadow: 0 8px 20px rgba(15, 99, 214, 0.3);
}
.btn-primary:hover { box-shadow: 0 12px 28px rgba(15, 99, 214, 0.4); }
.form-control, .form-select { background: #fff; border: 1px solid #dde6ff; color: var(--text-body); }
.form-control:focus, .form-select:focus {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 0.2rem rgba(15, 99, 214, 0.25);
}
<?php endif; ?>

/* Common Button Styles */
.btn-success { background: #28a745; border-color: #28a745; color: #fff; }
.btn-success:hover { background: #218838; border-color: #218838; }
.btn-info { background: #17a2b8; border-color: #17a2b8; color: #fff; }
.btn-info:hover { background: #138496; border-color: #138496; }
.btn-warning { background: #ffc107; border-color: #ffc107; color: #000; }
.btn-warning:hover { background: #e0a800; border-color: #e0a800; }
.btn-danger { background: #dc3545; border-color: #dc3545; color: #fff; }
.btn-danger:hover { background: #c82333; border-color: #c82333; }
.btn-secondary { background: #6c757d; border-color: #6c757d; color: #fff; }
.btn-secondary:hover { background: #5a6268; border-color: #545b62; }

/* Alert Styles */
.alert { border-left: 4px solid; border-radius: 12px; }
.alert-success { background: #d4edda; border-color: #28a745; color: #155724; }
.alert-danger { background: #f8d7da; border-color: #dc3545; color: #721c24; }
.alert-warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
.alert-info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }

/* Badge Styles */
.badge { font-weight: 600; padding: 6px 12px; border-radius: 20px; }

/* Pagination */
.pagination .page-link { border-radius: 8px; margin: 0 4px; }
.pagination .page-item.active .page-link { box-shadow: 0 4px 12px rgba(15, 99, 214, 0.3); }
</style>
