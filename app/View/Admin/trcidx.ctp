<?php //pr($journal); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
	<script type="application/javascript">
		$(document).ready(function() {
			// search and show/hide terms in card
			$("#trcsrc").on('keyup',function(){
				let val=$(this).val().toLowerCase().trim();
				let sets=$('.set');
				sets.show();
				if(val!=='') {
					sets.not('[data-search*="' + val + '"]').hide();
				}
			});
		});
	</script>
	<style>
		.list-responsive {
			max-height: calc(100vh - 250px);
			-webkit-overflow-scrolling: touch;
			overflow-y: auto;
		}
	</style>
	<script type="application/ld+json">
		<?php echo $jld; ?>
	</script>
</head>
<body>
<div class="container-fluid">
	<div class="row mt-3">
		<div class="col-10 offset-1">
			<h2>SciData Dataset: TRC Dataset from the <?php echo $journal['name']; ?></h2>
			<p>This dataset consists of <?php echo count($data); ?> files of thermophysical property data from the
				<?php echo $journal['name']; ?> (<?php echo strtoupper($journal['set']); ?>). Below is a searchable list
				of SciData JSON-LD files from <?php echo strtoupper($journal['set']); ?>, with references back to the
				ThermoML data that these files derive from. Search by chemical substance, condition/measured
				property, or paper title.</p>
			<div class="col-6 offset-3">
				<div class="input-group mb-3">
					<span class="input-group-text" id="trcsrclbl">Search</span>
					<input id="trcsrc" type="text" class="form-control" placeholder="Enter search term or scroll..." aria-label="Enter search term or scroll..." aria-describedby="trcsrclbl">
				</div>
			</div>
			<ul id="searchlist" class="list-group list-responsive">
				<?php foreach($data as $set) {
					$srcstr=strtolower($set['paper'].",".$set['subs'].",".$set['conds'].",".$set['props'])
					?>
					<li class="list-group-item set" data-search="<?=$srcstr;?>">
						<?php echo $this->Html->link($set['title'],$set['path']); ?>
						<?php echo $this->Html->link('<i class="bi bi-box-arrow-up-right"></i>',$set['trc'],['target'=>'_blank','escape'=>false]); ?>
						<?php echo "&nbsp;&nbsp;".$set['props']."&nbsp;"; ?>
						<?php echo "&nbsp;&nbsp;<em>".$set['subs']."</em>&nbsp;"; ?>
						<?php echo ' ('.$set['pnts'].' pnts)'; ?>
					</li>
				<?php } ?>
			</ul>
		</div>
	</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.5/dist/umd/popper.min.js" integrity="sha384-Xe+8cL9oJa6tN/veChSP7q+mnSPaj5Bcu9mPX5F5xIGE0DVittaqT5lorf0EI7Vk" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.min.js" integrity="sha384-ODmDIVzN+pFdexxHEHFBQH3/9/vQ9uori45z4JjnFsRydbmQbmL5t1tQ0culUzyK" crossorigin="anonymous"></script>
<script src="https://unpkg.com/bootstrap-table@1.21.0/dist/bootstrap-table.min.js"></script>
</body>
</html>
