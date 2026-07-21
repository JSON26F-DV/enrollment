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

    $from_address = getenv('MAIL_FROM_ADDRESS') ?: 'onboarding@resend.dev';
    $from_name = getenv('MAIL_FROM_NAME') ?: 'NCST Enrollment';
    
    $data = [
        'from' => "$from_name <$from_address>",
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
function send_approval_email($email, $first_name, $password = 'ncst123') {
    $subject = 'NCST Enrollment Approved – Your Student Account & Next Steps';
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #059669; color: white; padding: 30px 20px; text-align: center; border-radius: 12px 12px 0 0; }
            .header h1 { margin: 0; font-size: 26px; }
            .header p { margin: 8px 0 0; opacity: 0.9; font-size: 14px; }
            .content { padding: 30px 20px; background: #ffffff; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }
            .school-note { background: #eff6ff; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #1a73e8; }
            .school-note h3 { margin: 0 0 8px; color: #1a40e8; font-size: 16px; }
            .school-note ul { margin: 8px 0 0; padding-left: 20px; }
            .school-note li { margin-bottom: 6px; font-size: 14px; }
            .credentials { background: #f0fdf4; padding: 20px; border-radius: 10px; margin: 20px 0; border: 2px dashed #059669; text-align: center; }
            .credential-label { font-weight: bold; color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
            .credential-value { font-size: 18px; color: #059669; font-weight: bold; margin: 4px 0 12px; }
            .btn { display: inline-block; background: #059669; color: white !important; padding: 12px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; margin: 10px 0; }
            .info-box { background: #f9fafb; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #e5e7eb; }
            .info-box p { margin: 4px 0; font-size: 14px; }
            .footer { text-align: center; padding: 20px; background: #f9fafb; color: #666; font-size: 12px; border-radius: 0 0 12px 12px; border: 1px solid #e5e7eb; border-top: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Welcome to NCST!</h1>
                <p>Your enrollment application has been approved</p>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($first_name) . ',</p>
                <p>Congratulations! We are pleased to inform you that your enrollment application at <strong>National College of Science and Technology (NCST)</strong> has been <strong>approved</strong>.</p>

                <div class="school-note">
                    <h3>Next Steps — Please Proceed to the School</h3>
                    <p style="margin:0;font-size:14px;">Visit the <strong>Registrar\'s Office</strong> at the NCST campus to complete your enrollment. Please bring the following:</p>
                    <ul>
                        <li>Printed copy of this email or your Student Number</li>
                        <li>Original and photocopy of your PSA Birth Certificate</li>
                        <li>Original and photocopy of Form 138 / Report Card</li>
                        <li>Good Moral Certificate from your previous school</li>
                        <li>2 pcs. 2x2 ID photos</li>
                    </ul>
                </div>

                <p>In the meantime, you can access the <strong>Student Portal</strong> using the credentials below:</p>

                <div class="credentials">
                    <p class="credential-label">Email / Username</p>
                    <p class="credential-value">' . htmlspecialchars($email) . '</p>
                    <p class="credential-label">Temporary Password</p>
                    <p class="credential-value">' . htmlspecialchars($password) . '</p>
                    <a href="' . (defined('BASE_URL') ? BASE_URL . '/src/view/auth/login/loginpage.php' : '#') . '" class="btn">Login to Student Portal</a>
                </div>

                <div class="info-box">
                    <p><strong>Important Notes:</strong></p>
                    <p>• Change your password immediately after your first login.</p>
                    <p>• Keep your credentials confidential. Do not share them with anyone.</p>
                    <p>• If you have any questions, contact the Registrar\'s Office at (02) 1234-5678 or email registrar@ncst.edu.ph.</p>
                </div>

                <p>We are excited to have you as part of the NCST community! See you on campus.</p>
                <p>Best regards,<br><strong>NCST Admissions & Registrar Office</strong></p>
            </div>
            <div class="footer">
                <p>This is an automated message from the NCST Enrollment System. Please do not reply directly.</p>
                <p>&copy; ' . date('Y') . ' National College of Science and Technology. All rights reserved.</p>
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
    $subject = 'NCST Enrollment Application – Status Update';
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #dc2626; color: white; padding: 30px 20px; text-align: center; border-radius: 12px 12px 0 0; }
            .header h1 { margin: 0; font-size: 24px; }
            .header p { margin: 8px 0 0; opacity: 0.9; font-size: 14px; }
            .content { padding: 30px 20px; background: #ffffff; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }
            .reason-box { background: #fef2f2; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc2626; }
            .reason-box h3 { margin: 0 0 8px; color: #dc2626; font-size: 15px; }
            .reason-box p { margin: 0; font-size: 14px; }
            .help-box { background: #fffbeb; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #f59e0b; }
            .help-box h3 { margin: 0 0 8px; color: #b45309; font-size: 15px; }
            .help-box p { margin: 4px 0; font-size: 14px; }
            .footer { text-align: center; padding: 20px; background: #f9fafb; color: #666; font-size: 12px; border-radius: 0 0 12px 12px; border: 1px solid #e5e7eb; border-top: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Application Status Update</h1>
                <p>Regarding your enrollment at NCST</p>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($first_name) . ',</p>
                <p>Thank you for your interest in enrolling at <strong>National College of Science and Technology (NCST)</strong>.</p>
                <p>After a thorough review of your application and submitted documents, we regret to inform you that we are <strong>unable to approve your enrollment</strong> at this time.</p>';

    if ($reason) {
        $html .= '
                <div class="reason-box">
                    <h3>Reason for this decision:</h3>
                    <p>' . nl2br(htmlspecialchars($reason)) . '</p>
                </div>';
    }

    $html .= '
                <div class="help-box">
                    <h3>What you can do:</h3>
                    <p>• If you believe this decision was made in error, you may <strong>reapply</strong> by submitting a new application.</p>
                    <p>• Visit the Registrar\'s Office in person to discuss your situation with our admissions staff.</p>
                    <p>• Contact us at (02) 1234-5678 or email registrar@ncst.edu.ph for further assistance.</p>
                </div>

                <p>We appreciate the time and effort you put into your application. Should you have any questions or need clarification, please do not hesitate to reach out to us.</p>
                <p>We wish you the best in your future academic endeavors.</p>
                <p>Sincerely,<br><strong>NCST Admissions & Registrar Office</strong></p>
            </div>
            <div class="footer">
                <p>This is an automated message from the NCST Enrollment System. Please do not reply directly.</p>
                <p>&copy; ' . date('Y') . ' National College of Science and Technology. All rights reserved.</p>
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