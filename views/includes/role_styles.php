<?php
/**
 * Role-based styling for MediArchive
 * Returns CSS based on user role
 */
$role = $_SESSION['role'] ?? 'patient';
?>
<style>
<?php if ($role === 'web_admin'): ?>
/* Web Admin - Black Motif with High Contrast */
body {
    background: #0a0a0a;
    color: #e0e0e0;
}
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #1a1a1a 0%, #000000 100%);
    border-right: 1px solid #333;
}
.sidebar .nav-link {
    color: #e0e0e0;
    padding: 12px 20px;
    margin: 5px 0;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border-left: 3px solid #00ff88;
}
.main-content {
    padding: 30px;
    background: #0a0a0a;
    color: #e0e0e0;
}
.card {
    background: #1a1a1a;
    border: 1px solid #333;
    color: #e0e0e0;
}
.card-header {
    background: #252525;
    border-bottom: 1px solid #333;
    color: #fff;
}
.stats-card {
    border-left: 4px solid #00ff88;
    background: #1a1a1a;
}
.table {
    color: #e0e0e0;
    background: #1a1a1a;
}
.table thead th {
    border-bottom: 2px solid #333;
    color: #fff;
    background: #252525;
}
.table tbody tr {
    border-bottom: 1px solid #333;
    background: #1a1a1a;
}
.table tbody tr:hover {
    background: #252525;
}
.table-dark {
    background: #252525;
    color: #fff;
}
.badge {
    color: #000;
    font-weight: 600;
}
.text-muted {
    color: #999 !important;
}
.form-control, .form-select {
    background: #1a1a1a;
    border: 1px solid #333;
    color: #e0e0e0;
}
.form-control:focus, .form-select:focus {
    background: #1a1a1a;
    border-color: #00ff88;
    color: #fff;
    box-shadow: 0 0 0 0.2rem rgba(0, 255, 136, 0.25);
}
.form-label {
    color: #e0e0e0;
}
.btn-primary {
    background: #00ff88;
    border-color: #00ff88;
    color: #000;
    font-weight: 600;
}
.btn-primary:hover {
    background: #00cc6f;
    border-color: #00cc6f;
    color: #000;
}
.btn-success {
    background: #00ff88;
    border-color: #00ff88;
    color: #000;
    font-weight: 600;
}
.btn-success:hover {
    background: #00cc6f;
    border-color: #00cc6f;
    color: #000;
}
.btn-info {
    background: #00a8ff;
    border-color: #00a8ff;
    color: #000;
    font-weight: 600;
}
.btn-info:hover {
    background: #0088cc;
    border-color: #0088cc;
    color: #000;
}
.btn-warning {
    background: #ffaa00;
    border-color: #ffaa00;
    color: #000;
    font-weight: 600;
}
.btn-warning:hover {
    background: #cc8800;
    border-color: #cc8800;
    color: #000;
}
.btn-danger {
    background: #ff4444;
    border-color: #ff4444;
    color: #fff;
    font-weight: 600;
}
.btn-danger:hover {
    background: #cc0000;
    border-color: #cc0000;
    color: #fff;
}
.btn-secondary {
    background: #555;
    border-color: #555;
    color: #fff;
}
.btn-secondary:hover {
    background: #777;
    border-color: #777;
    color: #fff;
}
.alert {
    background: #1a1a1a;
    border: 1px solid #333;
    color: #e0e0e0;
}
.alert-success {
    background: #1a3a1a;
    border-color: #00ff88;
    color: #00ff88;
}
.alert-danger {
    background: #3a1a1a;
    border-color: #ff4444;
    color: #ff9999;
}
.alert-warning {
    background: #3a3a1a;
    border-color: #ffaa00;
    color: #ffcc66;
}
.alert-info {
    background: #1a2a3a;
    border-color: #00a8ff;
    color: #66ccff;
}
.pagination .page-link {
    background: #1a1a1a;
    border-color: #333;
    color: #00ff88;
}
.pagination .page-link:hover {
    background: #252525;
    border-color: #00ff88;
    color: #00ff88;
}
.pagination .page-item.active .page-link {
    background: #00ff88;
    border-color: #00ff88;
    color: #000;
}
<?php elseif ($role === 'clinic_admin'): ?>
/* Clinic Admin - Green Motif with High Contrast */
body {
    background: #f5f5f5;
    color: #333;
}
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%);
}
.sidebar .nav-link {
    color: white;
    padding: 12px 20px;
    margin: 5px 0;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
    color: #fff;
    border-left: 3px solid #fff;
}
.main-content {
    padding: 30px;
    background: #f5f5f5;
}
.card {
    background: #fff;
    border: 1px solid #e0e0e0;
    color: #333;
}
.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    color: #2e7d32;
    font-weight: 600;
}
.stats-card {
    border-left: 4px solid #2e7d32;
    background: #fff;
}
.table {
    color: #333;
    background: #fff;
}
.table thead th {
    border-bottom: 2px solid #2e7d32;
    color: #2e7d32;
    background: #f8f9fa;
    font-weight: 600;
}
.table tbody tr {
    border-bottom: 1px solid #e0e0e0;
}
.table tbody tr:hover {
    background: #f0f8f0;
}
.table-dark {
    background: #2e7d32;
    color: #fff;
}
.badge {
    font-weight: 600;
}
.text-muted {
    color: #666 !important;
}
.form-control, .form-select {
    background: #fff;
    border: 1px solid #ccc;
    color: #333;
}
.form-control:focus, .form-select:focus {
    border-color: #2e7d32;
    box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
}
.btn-primary {
    background: #2e7d32;
    border-color: #2e7d32;
    color: #fff;
    font-weight: 600;
}
.btn-primary:hover {
    background: #1b5e20;
    border-color: #1b5e20;
    color: #fff;
}
.btn-success {
    background: #28a745;
    border-color: #28a745;
    color: #fff;
    font-weight: 600;
}
.btn-success:hover {
    background: #218838;
    border-color: #218838;
    color: #fff;
}
.btn-info {
    background: #17a2b8;
    border-color: #17a2b8;
    color: #fff;
    font-weight: 600;
}
.btn-info:hover {
    background: #138496;
    border-color: #138496;
    color: #fff;
}
.btn-warning {
    background: #ffc107;
    border-color: #ffc107;
    color: #000;
    font-weight: 600;
}
.btn-warning:hover {
    background: #e0a800;
    border-color: #e0a800;
    color: #000;
}
.btn-danger {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
    font-weight: 600;
}
.btn-danger:hover {
    background: #c82333;
    border-color: #c82333;
    color: #fff;
}
.alert {
    border-left: 4px solid;
}
.alert-success {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}
.alert-danger {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}
.alert-warning {
    background: #fff3cd;
    border-color: #ffc107;
    color: #856404;
}
.alert-info {
    background: #d1ecf1;
    border-color: #17a2b8;
    color: #0c5460;
}
.pagination .page-link {
    color: #2e7d32;
}
.pagination .page-link:hover {
    background: #f0f8f0;
    color: #1b5e20;
}
.pagination .page-item.active .page-link {
    background: #2e7d32;
    border-color: #2e7d32;
    color: #fff;
}
<?php else: ?>
/* Patient - Blue Motif with High Contrast */
body {
    background: #f0f4f8;
    color: #333;
}
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #1976d2 0%, #0d47a1 100%);
}
.sidebar .nav-link {
    color: white;
    padding: 12px 20px;
    margin: 5px 0;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
    color: #fff;
    border-left: 3px solid #fff;
}
.main-content {
    padding: 30px;
    background: #f0f4f8;
}
.card {
    background: #fff;
    border: 1px solid #e0e0e0;
    color: #333;
}
.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    color: #1976d2;
    font-weight: 600;
}
.stats-card {
    border-left: 4px solid #1976d2;
    background: #fff;
}
.table {
    color: #333;
    background: #fff;
}
.table thead th {
    border-bottom: 2px solid #1976d2;
    color: #1976d2;
    background: #f8f9fa;
    font-weight: 600;
}
.table tbody tr {
    border-bottom: 1px solid #e0e0e0;
}
.table tbody tr:hover {
    background: #f0f4f8;
}
.table-dark {
    background: #1976d2;
    color: #fff;
}
.badge {
    font-weight: 600;
}
.text-muted {
    color: #666 !important;
}
.form-control, .form-select {
    background: #fff;
    border: 1px solid #ccc;
    color: #333;
}
.form-control:focus, .form-select:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
}
.btn-primary {
    background: #1976d2;
    border-color: #1976d2;
    color: #fff;
    font-weight: 600;
}
.btn-primary:hover {
    background: #1565c0;
    border-color: #1565c0;
    color: #fff;
}
.btn-success {
    background: #28a745;
    border-color: #28a745;
    color: #fff;
    font-weight: 600;
}
.btn-success:hover {
    background: #218838;
    border-color: #218838;
    color: #fff;
}
.btn-info {
    background: #17a2b8;
    border-color: #17a2b8;
    color: #fff;
    font-weight: 600;
}
.btn-info:hover {
    background: #138496;
    border-color: #138496;
    color: #fff;
}
.btn-warning {
    background: #ffc107;
    border-color: #ffc107;
    color: #000;
    font-weight: 600;
}
.btn-warning:hover {
    background: #e0a800;
    border-color: #e0a800;
    color: #000;
}
.btn-danger {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
    font-weight: 600;
}
.btn-danger:hover {
    background: #c82333;
    border-color: #c82333;
    color: #fff;
}
.alert {
    border-left: 4px solid;
}
.alert-success {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}
.alert-danger {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}
.alert-warning {
    background: #fff3cd;
    border-color: #ffc107;
    color: #856404;
}
.alert-info {
    background: #d1ecf1;
    border-color: #17a2b8;
    color: #0c5460;
}
.pagination .page-link {
    color: #1976d2;
}
.pagination .page-link:hover {
    background: #f0f4f8;
    color: #1565c0;
}
.pagination .page-item.active .page-link {
    background: #1976d2;
    border-color: #1976d2;
    color: #fff;
}
<?php endif; ?>
</style>

