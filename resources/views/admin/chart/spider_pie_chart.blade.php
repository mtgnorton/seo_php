<style>
.chart_tab span{background:#fff;color:#333;border-bottom:2px solid transparent;cursor:pointer;}
.chart_tab span.cur{color:#0769ba;border-color:#0769ba;}
.chart_tab span:hover{color:#0769ba;border-color:#0769ba;}
#doughnut .loading{position:absolute;top:45%;left:50%;margin-left:-40px;background:#fff;}
.type_tab{position:absolute;left:0;z-index:99;height:31px;line-height:31px;top:10px;font-family:Microsoft Yahei;border-top:2px solid #40AA52;}
.type_tab span{font-size:13px;cursor:pointer;padding:6px 15px;background:#eee;}
.type_tab span.cur{background:#40AA52;color:#fff;}
</style>
<div>
    <div style="width: 30%; float:left;">
        <div>
            <div style="position: relative;text-align:right">
                <div id="pie_tab" class="chart_tab" style="text-align: center;">
                    <span class="cur" data="0">今日</span>
                    <span data="1">昨日</span>
                    <span data="7">7日</span>
                    <span data="30">30日</span>
                    <span data="365">1年</span>
                </div>
            </div>
        </div>
        <div>
            <canvas id="doughnut" height="300"></canvas>
        </div>
    </div>

    <div style="width: 60%; float:right;">
        <div>
            <div style="position: relative;text-align:right">
                <div id="day_tab" class="chart_tab" style="text-align: center;">
                    <span class="cur" data="0">今日</span>
                    <span data="1">昨日</span>
                    <span data="2">前日</span>
                </div>
            </div>
        </div>
        <div>
            <canvas id="line1" height="300"></canvas>
        </div>
    </div>
</div>
<div style="clear:both;"></div>
<div style="margin-top: 20px;">
    <div>
        <div style="position: relative; text-align:left;">
            <div class="type_tab" id="week_type_tab">
                <span class="cur" data="all">全部</span>
                <span data="detail">明细</span>
            </div>
            <div id="week_tab" class="chart_tab" style="margin-left: 150px; padding-top: 10px">
                <span data="10" class="cur">近10日</span>
                <span data="30">近30日</span>
                <span data="365">近1年</span>
            </div>
        </div>
    </div>
    <div>
        <canvas id="line2" height="300"></canvas>
    </div>
</div>
<script>
$(function () {
    $.ajax({
        url: '/admin/spider-records/pie-data',
        method: 'get',
        dataType: 'json'
    }).done(function (data) {
        var ctx = document.getElementById('doughnut').getContext('2d');
        var config = {
            type: 'pie',
            data: {
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 132)',
                        'rgb(255, 205, 86)',
                        'rgb(255,182,193)',
                        'rgb(148,0,211)',
                        'rgb(65,105,225)',
                        'rgb(127,255,170)',
                    ]
                }],
                labels: data.labels
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 0,
                        right: 0,
                        top: 0,
                        bottom: 0
                    }
                },
                tooltips: {
                    mode: 'point'
                },
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    position: 'top',
                    fontSize: 19,
                    text: data.title,
                },
            }
        };
        var pieChart = new Chart(ctx, config);
    
        $('#pie_tab span').click(function(){
            $(this).siblings().removeClass('cur').end().addClass('cur');
            var gurl='/admin/spider-records/pie-data?'+'day='+$(this).attr('data');
            $('#doughnut').css({ opacity: 0.3 });
            $.ajax({
                url:gurl,
                method: 'get',
                dataType: 'json'
            }).done(function (data) {
                pieChart.data.datasets[0].data = data.values;
                pieChart.options.title.text = data.title;
                pieChart.update();
            }).fail(function (xhr) {
                swal('获取数据失败');
            }).always(function () {
                $('#doughnut').css({ opacity:1 });
            });
        });
    }).fail(function (xhr) {
        swal('获取数据失败');
    });

    
    $.ajax({
        url: '/admin/spider-records/hour-data',
        method: 'get',
        dataType: 'json'
    }).done(function (data) {
        var ctx = document.getElementById('line1').getContext('2d');
        var config = {
            type: 'line',
            data: {
                datasets: data.values,
                labels: data.labels
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 0,
                        right: 0,
                        top: 0,
                        bottom: 0
                    }
                },
                tooltips: {
                    mode: 'point'
                },
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    position: 'top',
                    fontSize: 19,
                    text: data.title,
                },
            }
        };
        var line1Chart = new Chart(ctx, config);
    
        $('#day_tab span').click(function(){
            $(this).siblings().removeClass('cur').end().addClass('cur');
            var gurl='/admin/spider-records/hour-data?'+'day='+$(this).attr('data');
            $('#line1').css({ opacity: 0.3 });
            $.ajax({
                url:gurl,
                method: 'get',
                dataType: 'json'
            }).done(function (data) {
                line1Chart.data.datasets = data.values;
                line1Chart.options.title.text = data.title;
                line1Chart.update();
            }).fail(function (xhr) {
                swal('获取数据失败');
            }).always(function () {
                $('#line1').css({ opacity:1 });
            });
        });
    }).fail(function (xhr) {
        swal('获取数据失败');
    });
    
    $.ajax({
        url: '/admin/spider-records/day-data',
        method: 'get',
        dataType: 'json'
    }).done(function (data) {
        var ctx = document.getElementById('line2').getContext('2d');
        var config = {
            type: 'line',
            data: {
                datasets: data.values,
                labels: data.labels
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 0,
                        right: 0,
                        top: 0,
                        bottom: 0
                    }
                },
                tooltips: {
                    mode: 'point'
                },
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    position: 'top',
                    fontSize: 19,
                    text: data.title,
                },
            }
        };
        var line2Chart = new Chart(ctx, config);
    
        $('#week_tab span').click(function(){
            $(this).siblings().removeClass('cur').end().addClass('cur');
            var day = $(this).attr('data');
            var type = $('#week_type_tab .cur').attr('data');
            var gurl='/admin/spider-records/day-data?'+'day='+day+'&type='+type;
            $('#line2').css({ opacity: 0.3 });
            $.ajax({
                url:gurl,
                method: 'get',
                dataType: 'json'
            }).done(function (data) {
                line2Chart.data.datasets = data.values;
                line2Chart.data.labels = data.labels;
                line2Chart.update();
            }).fail(function (xhr) {
                swal('获取数据失败');
            }).always(function () {
                $('#line2').css({ opacity:1 });
            });
        });
    
        $('#week_type_tab span').click(function(){
            $(this).siblings().removeClass('cur').end().addClass('cur');
            var type = $(this).attr('data');
            var day = $('#week_tab .cur').attr('data');
            var gurl='/admin/spider-records/day-data?'+'day='+day+'&type='+type;
            $('#line2').css({ opacity: 0.3 });
            $.ajax({
                url:gurl,
                method: 'get',
                dataType: 'json'
            }).done(function (data) {
                line2Chart.data.datasets = data.values;
                line2Chart.data.labels = data.labels;
                line2Chart.update();
            }).fail(function (xhr) {
                swal('获取数据失败');
            }).always(function () {
                $('#line2').css({ opacity:1 });
            });
        });
    }).fail(function (xhr) {
        swal('获取数据失败');
    });
});
</script>