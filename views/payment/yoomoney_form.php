<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting...</title>
    <style>body{background:#111;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;}</style>
</head>
<body>
    <div style="text-align:center;">
        <h3>Secure Redirect...</h3>
        <p>Please wait.</p>
        
        <form id="payform" action="https://yoomoney.ru/quickpay/confirm.xml" method="POST">
            <!-- FIX: XSS Protection via htmlspecialchars -->
            <input type="hidden" name="receiver" value="<?= htmlspecialchars($_GET['wallet']) ?>">
            <input type="hidden" name="formcomment" value="Order #<?= htmlspecialchars($_GET['id']) ?>">
            <input type="hidden" name="short-dest" value="Order #<?= htmlspecialchars($_GET['id']) ?>">
            <input type="hidden" name="label" value="<?= htmlspecialchars($_GET['label']) ?>">
            <input type="hidden" name="quickpay-form" value="shop">
            <input type="hidden" name="targets" value="Order #<?= htmlspecialchars($_GET['id']) ?>">
            <input type="hidden" name="sum" value="<?= htmlspecialchars($_GET['sum']) ?>">
            <input type="hidden" name="paymentType" value="AC"> 
            
            <input type="hidden" name="successURL" value="<?= BASE_URL ?>/payment/success">
        </form>
        <script nonce="<?= CSP_NONCE ?>">document.getElementById('payform').submit();</script>
    </div>
</body>
</html>