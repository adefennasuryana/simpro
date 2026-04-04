<?php
/**
 * Root index.php - redirect ke login atau dashboard
 */
session_start();
if (!empty($_SESSION['id_user'])) {
    header('Location: dashboard/index.php');
} else {
    header('Location: auth/login.php');
}
exit;
