<!DOCTYPE html>
<html dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'Arial'; direction: rtl; text-align: right; font-size: 14px; }
    h2   { text-align: center; }
    .box { margin-bottom: 15px; }
    .label { font-weight: bold; }
</style>
</head>
<body>

<h2>التقرير الطبي</h2>

<div class="box">
    <span class="label">المريض: </span> {{ $report->patient?->user?->name }}
</div>
<div class="box">
    <span class="label">الدكتور: </span> {{ $report->doctor?->user?->name }}
</div>
<div class="box">
    <span class="label">التاريخ: </span> {{ $report->report_date }}
</div>
<div class="box">
    <span class="label">المحتوى:</span>
    <p>{!! nl2br(e($report->content)) !!}</p>
</div>

</body>
</html>