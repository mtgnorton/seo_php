<link rel="stylesheet" href="/custom/css/swiper.min.css">
<style>
    .home-chart-time{
        width: 100%;
        height: 130px;
        color: #6B7D90;
        font-size: 30px;
        margin: 0 auto;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    .home-chart-box{
        width: 100%;
        height: 130px;
        margin: 20px auto 0;
    }

    .home-chart-list{ min-width: 100%; height: 280px; background-color: #FFFFFF; padding: 20px 30px; box-sizing: border-box;}
    .home-chart-content{ height: 240px; vertical-align: top; display: inline-block; background: #EFF4F8; border-radius: 18px; padding: 15px 30px; box-sizing: border-box; }
    .home-chart-content .home-chart-name{ height: 40px; line-height: 40px;  margin-bottom: 10px; font-weight: 600; color: #6B7D90; font-size: 16px; position: relative; }
    .home-chart-content .home-chart-name::after{ content: ""; position: absolute; width: 20px; height: 6px; background: #EA6756; border-radius: 4px; top: 17px; left: -30px; }
    .home-chart-content:last-child{ margin-right: 0; }
    .home-chart-content .home-chart-title{ color: #6B7D90; text-align: center; width: 100%; font-size: 14px; }
    .home-chart-content canvas{ height: 130px !important; display: inline-block; margin: 10px auto; }
    .swiper-button-next, .swiper-button-prev{
        width: 15px;
        height: 23px;
        margin-top: -12px;
        background-size: 15px 23px;
    }
    .swiper-button-next, .swiper-container-rtl .swiper-button-prev{
        background-image: url('/asset/imgs/default_icon/r-icon.png');
    }
    .swiper-button-prev, .swiper-container-rtl .swiper-button-next{
        background-image: url('/asset/imgs/default_icon/r-icon.png');
        transform: rotate(180deg);
    }
</style>
<!-- Swiper -->
<div class="swiper-container home-chart-list">
    <div class="swiper-wrapper">

        <div class="swiper-slide home-chart-content">
            <div class="home-chart-name">cpu使用率</div>
            <div id="home3" class="home-chart-box" data-value="{{$cpuPercent}}"></div>
            <div class="home-chart-title">{{$cpuAmount}}核心</div>
        </div>
        <div class="swiper-slide home-chart-content">
            <div class="home-chart-name">内存使用率</div>
            <div id="home4" class="home-chart-box" data-value="{{$memPercent}}"></div>
            <div class="home-chart-title">{{$memUsed}}/{{$memTotal}}(MB)</div>
        </div>
        <div class="swiper-slide home-chart-content">
            <div class="home-chart-name">磁盘使用率</div>
            <div id="home5" class="home-chart-box" data-value="{{$diskPercent}}"></div>
            <div class="home-chart-title">{{$diskUsed}}/{{$diskTotal}}</div>
        </div>
        <div class="swiper-slide home-chart-content">
            <div class="home-chart-name">负载状态</div>
            <div id="home2" class="home-chart-box" data-value="{{$loadPercent}}"></div>
            <div class="home-chart-title">负载状态</div>
        </div>
        <div class="swiper-slide home-chart-content">
            <div class="home-chart-name">服务器运行时间</div>
            <div class="home-chart-time">
                <p>{{$upTime}}</p>
            </div>
            <div class="home-chart-title">服务器运行时间</div>
        </div>

    </div>
    <!-- Add Arrows -->
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
</div>
<script src="/custom/js/echarts.min.js"></script>
<script src="/custom/js/swiper.min.js"></script>
<script>
    function ecInit(dom, value1, value2, name) {
        var dom = dom; //就是你要放入的盒子元素
        var myChart = echarts.init(dom);
        var option = {
            tooltip: {
                show: false,
                trigger: 'item',
                formatter: "{a} : {c} ({d}%)"
            },
            title: {
                text: value1 + '%',  //图形标题，配置在中间对应效果图的80%
                left: "center",
                top: "center",
                textStyle: {
                    color: "rgb(145, 204, 118)",
                    fontSize: 20,
                    align: "center"
                }
            },
            series: [
            {
                type: 'pie',
                radius: ['70%', '90%'],  //设置内外环半径,两者差值越大，环越粗
                hoverAnimation: false,　 //移入图形是否放大
                label: {     //对应效果图中的Angular显示与否以及设置样式
                    normal: {
                        show: false,
                        position: 'center',
                        padding: [0, 0, 20, 0],  //设置字angular的边距
                        fontSize: 16,
                    }
                },
                labelLine: {
                    normal: {  //label线不显示
                        show: false
                    }
                },
                data: [
                    {
                        name: name,   //数据，name对应Angular
                        value: value1,  //对应显示的部分数据即80%
                        itemStyle: {
                            normal: {
                                color: 'rgb(255, 0, 0)'
                            }
                        }
                    },
                    {
                        value: value2,
                        itemStyle: {
                            normal: {
                                color: 'rgb(56, 209, 79)',
                            }
                        }
                    }
                ]
            }
            ]
        };
        myChart.setOption(option, true);
        return myChart
    }
    $(function () {
        setTimeout(() => {
            var ctx2Data = $('#home2').attr('data-value') || 1;
            var ctx2Chart = ecInit(document.getElementById('home2'), ctx2Data*1, 100-ctx2Data*1, '负载状态')

            var ctx3Data = $('#home3').attr('data-value') || 1;
            var ctx3Chart = ecInit(document.getElementById('home3'), ctx3Data*1, 100-ctx3Data*1, 'cpu使用率')

            var ctx4Data = $('#home4').attr('data-value') || 1;
            var ctx4Chart =ecInit(document.getElementById('home4'), ctx4Data*1, 100-ctx4Data*1, '内存使用率')

            var ctx5Data = $('#home5').attr('data-value') || 1;
            ctx5Data = ctx5Data.replace('%', '');
            var ctx5Chart =ecInit(document.getElementById('home5'), ctx5Data*1, 100-ctx5Data*1, '磁盘使用率')

            setTimeout(()=>{
                window.onresize = function () {
                    ctx2Chart.resize();
                    ctx3Chart.resize();
                    ctx4Chart.resize();
                    ctx5Chart.resize();
                }
            }, 200)
        }, 500);
    });

    var swiper = new Swiper('.swiper-container', {
        slidesPerView: 4,
        spaceBetween: 30,
        slidesPerGroup: 4,
        loop: false,
        loopFillGroupWithBlank: true,
        pagination: {
            el: '.swiper-pagination',
            clickable: false,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });
</script>
