
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {packages: ['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            <?php
            foreach($xy as $pt) {
                echo json_encode($pt).',';
            }?>]);


        var options = {
            title: '<?php echo ($title);?>',
            titleTextStyle: { fontSize: 16 },
            hAxis: {title: '<?php echo ($xlabel);?>', minValue: <?php echo ($minx);?>, maxValue: <?php echo ($maxx);?>},
            vAxis: {title: '<?php echo ($ylabel);?>', minValue: <?php echo ($miny);?>, maxValue: <?php echo ($maxy);?>},
            legend: { position: 'top', maxLines: 3, textStyle:{fontSize:15}},
            trendlines: {
            	0: { type: 'linear',visibleInLegend: true },
                1: { type: 'linear',visibleInLegend: true },
                2: { type: 'linear',visibleInLegend: true }
			},
            explorer: { actions: ['dragToZoom', 'rightClickToReset'], maxZoomIn: 20.0}
        };

        // Instantiate and draw the chart.
        var chart = new google.visualization.ScatterChart(document.getElementById('myChart'));
        chart.draw(data, options);
    }

</script>
<div id="myChart" class="chart" style="height:600px;"></div>
