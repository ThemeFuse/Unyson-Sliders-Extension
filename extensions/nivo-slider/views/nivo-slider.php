<?php if (!defined('FW')) die('Forbidden'); ?>

<?php if (isset($data['slides'])): ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.nivoSlider').nivoSlider({effect:'fade'});
		});
	</script>
	<!--Slider-->
	<section class="wrap-nivoslider theme-default">
		<div class="nivoSlider">
			<?php foreach ($data['slides'] as $id => $slide): ?>
			<img  width="<?php echo esc_attr($dimensions['width']); ?>"
			      height="<?php echo esc_attr($dimensions['height']); ?>"
			      src="<?php echo esc_attr(fw_resize($slide['src'], $dimensions['width'], $dimensions['height'], true)); ?>"
			      alt="<?php echo esc_attr($slide['title']); ?>"
			      title="#nivo-<?php echo esc_attr($id); ?>" />
			<?php endforeach; ?>
		</div>
		<?php foreach ($data['slides'] as $id => $slide): ?>
		<div id="nivo-<?php echo esc_attr($id) ?>" class="nivo-html-caption">
			<?php if (empty($slide['desc'])): ?>
				<span><?php echo $slide['title'] ?></span>
			<?php else: ?>
				<span><strong><?php echo $slide['title'] ?></strong> - <?php echo $slide['desc'] ?><span>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</section>
	<!--/Slider-->
<?php endif; ?>
