<style>
.chart_main{ width:100%; background:#fff; padding: 20px 30px; box-sizing: border-box;}
.chart_box{ width: calc(50% - 20px); display: inline-block; margin-right: 30px; background: #EFF4F8; border-radius: 18px; padding: 15px 40px; box-sizing: border-box; vertical-align: top; }
.chart_box:last-child{ margin-right: 0; }
.chart-name{ height: 40px; line-height: 40px; margin-bottom: 10px; font-weight: 600; color: #6B7D90; font-size: 16px; position: relative; }
.chart-name::after{ content: ""; position: absolute; width: 20px; height: 6px; background: #EA6756; border-radius: 4px; top: 17px; left: -40px; }
.chart_tab{ text-align:left; margin-bottom: 20px; }
.chart_tab span{color:#44627C;border-bottom:2px solid transparent;cursor:pointer; margin-right: 20px;display: inline-block;}
.chart_tab span.cur{color:#3E4AF5;border-color:#3E4AF5;font-weight: 600;}
.chart_tab span:hover{color:#3E4AF5;border-color:#3E4AF5;}
#doughnut .loading{position:absolute;top:45%;left:50%;margin-left:-40px;background:#fff;}
.type_tab{position:absolute;left:0;z-index:99;height:31px;line-height:31px;top:10px;font-family:Microsoft Yahei;border-top:2px solid #40AA52;}
.type_tab span{font-size:13px;cursor:pointer;padding:6px 15px;background:#eee;}
.type_tab span.cur{background:#40AA52;color:#fff;}
</style>
<div class="chart_main">
    <div class="chart_box">
        <div class="chart-name chart_name">今日访问比率</div>
        <div style="position: relative;text-align:right">
            <div id="pie_tab" class="chart_tab">
                <span class="cur" data="0">今日</span>
                <span data="1">昨日</span>
                <span data="7">7日</span>
                <span data="30">30日</span>
                <span data="365">1年</span>
            </div>
        </div>
        <div>
            <canvas id="doughnut" height="300"></canvas>
        </div>
    </div>
    <div class="chart_box">
        <div class="chart-name line_name">今日蜘蛛时段走势图</div>
        <div style="position: relative;text-align:right">
            <div id="day_tab" class="chart_tab">
                <span class="cur" data="0">今日</span>
                <span data="1">昨日</span>
                <span data="2">前日</span>
            </div>
        </div>
        <div>
            <canvas id="line1" height="300"></canvas>
        </div>
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
                        'rgb(139,69,19)',
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
                    position: 'bottom'
                },
                // title: {
                //     display: true,
                //     position: 'top',
                //     fontSize: 19,
                //     text: data.title,
                // },
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
                $('.chart_name').text(data.title)
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
                    position: 'bottom',
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
                $('.line_name').text(data.title)
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
});
</script>