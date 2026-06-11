<!DOCTYPE html>
<html dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: 'Arial';
        direction: rtl;
        text-align: right;
        font-size: 14px;
        margin: 40px;
    }
    h2 {
        text-align: center;
        margin-bottom: 10px;
        font-size: 20px;
    }
    .subtitle {
        text-align: center;
        color: #555;
        margin-bottom: 30px;
        font-size: 13px;
    }
    .divider {
        border: 1px solid #ccc;
        margin: 20px 0;
    }
    .box {
        margin-bottom: 12px;
    }
    .label {
        font-weight: bold;
    }
    .footer {
        margin-top: 60px;
        text-align: center;
        color: #888;
        font-size: 12px;
    }
</style>
</head>
<body>

<h2>وصل دفع</h2>

<hr class="divider">

<div class="box">
    <span class="label">رقم الوصل: </span>
    <span>#{{ $payment['id'] }}</span>
</div>

<div class="box">
    <span class="label">اسم الدكتور: </span>
    <span>{{ $payment['doctor_name'] }}</span>
</div>

<div class="box">
    <span class="label">تاريخ الدفع: </span>
    <span>{{ \Carbon\Carbon::parse($payment['payment_date'])->format('Y-m-d') }}</span>
</div>

<hr class="divider">

<div class="box">
    <span class="label">المبلغ بالدولار: </span>
    <span>{{ number_format($payment['amount_usd'], 2) }} $</span>
</div>

<div class="box">
    <span class="label">المبلغ بالليرة: </span>
    <span>{{ number_format($payment['amount_syp'], 2) }} ل.س</span>
</div>

<div class="box">
    <span class="label">سعر الصرف: </span>
    <span>{{ number_format($payment['exchange_rate'], 2) }} ل.س / $</span>
</div>

<hr class="divider">


</body>
</html>