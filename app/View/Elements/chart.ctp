<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {packages: ['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        let data = google.visualization.arrayToDataTable([
            <?php
            foreach($xy as $pt) {
                echo json_encode($pt).',';
            }?>]);


        let options = {
            title: '<?php echo ($title);?>',
            titleTextStyle: { fontSize: 16 },
            hAxis: {title: '<?php echo ($xlabel);?>', minValue: <?php echo ($minx);?>, maxValue: <?php echo ($maxx);?>},
            vAxis: {title: '<?php echo ($ylabel);?>', minValue: <?php echo ($miny);?>, maxValue: <?php echo ($maxy);?>},
            legend: { position: 'bottom', maxLines: 3, textStyle:{fontSize:15}},
            trendlines: {
            	0: { type: 'polynomial',visibleInLegend: true },
                1: { type: 'polynomial',visibleInLegend: true },
                2: { type: 'polynomial',visibleInLegend: true }
			},
            explorer: { actions: ['dragToZoom', 'rightClickToReset'], maxZoomIn: 20.0}
        };

        // Instantiate and draw the chart.
        let chart = new google.visualization.ScatterChart(document.getElementById('myChart'));
        chart.draw(data, options);
    }

	$(window).resize(function(){
		drawChart();
	});
</script>
<div class="panel panel-info">
	<div class="panel-body" style="padding: 15px 0;">
		<div id="myChart" class="chart" style="max-height: 500px;min-height: 300px;"></div>
	</div>
</div>
