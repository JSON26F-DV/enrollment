<?php
/**
 * Email Helper using Resend.com API
 * 
 * Configuration:
 * Set environment variable RESEND_API_KEY with your Resend API key
 */

if (!defined('RESEND_API_KEY')) {
    // You can set this in your server environment or here
    // define('RESEND_API_KEY', 're_your_api_key_here');
}

/**
 * Send email using Resend API
 */
function send_email($to, $subject, $html, $text = null) {
    $api_key = getenv('RESEND_API_KEY') ?: '';
    
    if (empty($api_key)) {
        // Log error but don't fail - fall back to logging
        error_log("Resend API key not configured. Email not sent to: $to");
        return false;
    }
    
    $data = [
        'from' => 'NCST Enrollment <noreply@ncst.edu.ph>',
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
    ];
    
    if ($text) {
        $data['text'] = $text;
    }
    
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($http_code >= 200 && $http_code < 300) {
        return true;
    }
    
    error_log("Resend API error: " . ($result['message'] ?? 'Unknown error'));
    return false;
}

/**
 * Send application confirmation email
 */
function send_application_confirmation($email, $first_name) {
    $subject = 'NCST Enrollment Application Received';
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a73e8; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>NCST Enrollment</h1>
            </div>
            <div class="content">
                <p>Hello ' . htmlspecialchars($first_name) . ',</p>
                <p>Thank you for submitting your enrollment application to NCST!</p>
                <p>Your application is now being reviewed by our admissions office. You will receive another email once your application has been processed.</p>
                <p><strong>What happens next?</strong></p>
                <ul>
                    <li>Our registrar will review your application and documents</li>
                    <li>You will receive your Student Number via email once approved</li>
                    <li>A temporary password will be provided for your first login</li>
                </ul>
                <p>If you have any questions, please contact our admissions office.</p>
                <p>Best regards,<br>NCST Admissions Office</p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply directly to this email.</p>
                <p>&copy; ' . date('Y') . ' National College of Science and Technology</p>
            </div>
        </div>
    </body>
    </html>';
    
    return send_email($email, $subject, $html);
}

/**
 * Send approval email with student credentials
 */
function send_approval_email($email, $first_name) {
    $subject = 'NCST Enrollment Approved - Your Student Account';
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #059669; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .credentials { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .credential-label { font-weight: bold; color: #666; }
            .credential-value { font-size: 18px; color: #1a73e8; }
            .warning { background: #fef3c7; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 Congratulations!</h1>
            </div>
            <div class="content">
                <p>Hello ' . htmlspecialchars($first_name) . ',</p>
                <p>Great news! Your enrollment application has been <strong>approved</strong>.</p>
                <p>Here are your account credentials:</p>
                
                <div class="credentials">
                    <p class="credential-label">Email:</p>
                    <p class="credential-value">' . htmlspecialchars($email) . '</p>
                    <br>
                    <p class="credential-label">Default Password:</p>
                    <p class="credential-value">ncst123</p>
                </div>
                
                <div class="warning">
                    <strong>⚠️ Important:</strong>
                    <ul>
                        <li>Please change your password after your first login</li>
                        <li>Do not share your credentials with anyone</li>
                    </ul>
                </div>
                
                <p>Click the link below to login to your student portal:</p>
                <p><a href="' . (defined('BASE_URL') ? BASE_URL : '') . '/src/view/auth/login/loginpage.php" style="display: inline-block; background: #1a73e8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Login to Student Portal</a></p>
                
                <p>If you have any questions, please contact our admissions office.</p>
                <p>Welcome to NCST!<br><strong>NCST Admissions Office</strong></p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply directly to this email.</p>
                <p>&copy; ' . date('Y') . ' National College of Science and Technology</p>
            </div>
        </div>
    </body>
    </html>';
    
    return send_email($email, $subject, $html);
}

/**
 * Send rejection email
 */
function send_rejection_email($email, $first_name, $reason = null) {
    $subject = 'NCST Enrollment Application Update';
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>NCST Enrollment Update</h1>
            </div>
            <div class="content">
                <p>Hello ' . htmlspecialchars($first_name) . ',</p>
                <p>Thank you for your interest in NCST.</p>
                <p>After reviewing your application, we regret to inform you that we are unable to approve your enrollment at this time.</p>';
    
    if ($reason) {
        $html .= '<p><strong>Reason:</strong> ' . htmlspecialchars($reason) . '</p>';
    }
    
    $html .= '
                <p>If you believe this is an error or would like more information, please contact our admissions office.</p>
                <p>Thank you for considering NCST.</p>
                <p>Best regards,<br>NCST Admissions Office</p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply directly to this email.</p>
                <p>&copy; ' . date('Y') . ' National College of Science and Technology</p>
            </div>
        </div>
    </body>
    </html>';
    
    return send_email($email, $subject, $html);
}

/**
 * Send revision request email
 */
function send_revision_email($email, $first_name, $required_revisions) {
    $subject = 'NCST Enrollment Application - Additional Information Required';
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f59e0b; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .revisions { background: #fffbeb; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #fcd34d; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Action Required</h1>
            </div>
            <div class="content">
                <p>Hello ' . htmlspecialchars($first_name) . ',</p>
                <p>Thank you for submitting your enrollment application to NCST.</p>
                <p>We have reviewed your application and need some additional information:</p>
                
                <div class="revisions">
                    <strong>Please address the following:</strong>
                    <p>' . nl2br(htmlspecialchars($required_revisions)) . '</p>
                </div>
                
                <p>Please submit the required information at your earliest convenience so we can continue processing your application.</p>
                <p>If you have any questions, please contact our admissions office.</p>
                <p>Best regards,<br>NCST Admissions Office</p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply directly to this email.</p>
                <p>&copy; ' . date('Y') . ' National College of Science and Technology</p>
            </div>
        </div>
    </body>
    </html>';
    
    return send_email($email, $subject, $html);
}