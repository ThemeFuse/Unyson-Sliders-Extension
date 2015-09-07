<?php if (!defined('FW')) die('Forbidden'); ?>
<?php if (isset($data['slides'])): ?>
	<script type="text/javascript">
		jQuery('document').ready(function () {
			jQuery('.bxslider').bxSlider();
		});
	</script>
	<ul class="bxslider">
		<?php foreach ($data['slides'] as $slide): ?>
			<li>
				<?php if ($slide['multimedia_type'] === 'video') : ?>
					<?php echo fw_oembed_get($slide['src'], $dimensions); ?>
				<?php else: ?>
					<img src="<?php echo esc_attr(fw_resize($slide['src'], $dimensions['width'], $dimensions['height'], true)); ?>"
					     alt="<?php echo esc_attr($slide['title']) ?>" width="<?php echo esc_attr($dimensions['width']); ?>"
					     height="<?php echo esc_attr($dimensions['height']); ?>"/>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
