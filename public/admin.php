<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المشرف</title>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <header>
        <h1>تسجيل الدخول - لوحة التحكم</h1>
    </header>

    <main>
        <form action="admin_login.php" method="post">
            <label for="email">البريد الإلكتروني:</label>
            <input type="email" id="email" name="email" required>
            <br>
            <label for="password">كلمة المرور:</label>
            <input type="password" id="password" name="password" required>
            <br>
            <button type="submit">تسجيل الدخول</button>
        </form>
    </main>

</body>
</html>
