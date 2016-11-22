<?php if (!defined('FW')) die('Forbidden'); ?>

<?php if (isset($data['slides'])): ?>

	<div class="owl-carousel owl-theme">
		<?php foreach ($data['slides'] as $slide): ?>
			<?php if ($slide['multimedia_type'] === 'video'): ?>
				<?php
				if (false === ($oembed = get_site_transient($transient = 'fw:oembed:'. md5($slide['src'])))) {
					// Cache wp_oembed_get() to prevent request on every render
					set_site_transient(
						$transient,
						// do strlen() because wp_oembed_get() can be false and it will mess the above `if`
						$oembed = strlen(wp_oembed_get($slide['src'])),
						MINUTE_IN_SECONDS
					);
				}
				?>
				<?php if ($oembed): ?>
				<div class="item-video" style="height:<?php echo esc_attr($dimensions['height']); ?>px;">
					<a class="owl-video" href="<?php echo esc_attr($slide['src']); ?>"></a>
				</div>
				<?php endif; ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function ($) {
			$('.owl-carousel').owlCarousel({
				items: 1,
				loop: true,
				margin: 10,
				video: true,
				center: true
			});
		});
	</script>

<?php endif; ?>
