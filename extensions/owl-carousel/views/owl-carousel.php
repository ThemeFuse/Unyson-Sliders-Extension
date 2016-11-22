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
				<div class="item-video" style="height:<?php echo esc_attr($dimensions['height']); ?>px;">
					<?php if ($oembed): ?>
						<a class="owl-video" href="<?php echo esc_attr($slide['src']); ?>"></a>
					<?php else: // fixes https://github.com/ThemeFuse/Unyson/issues/2189 ?>
						<?php
						$video_type = parse_url($slide['src']);
						$video_type = explode('.', $video_type['path']);
						$video_type = array_pop($video_type);
						$video_type = strtolower($video_type);
						?>
						<video controls width="100%" height="100%">
							<source src="<?php echo esc_attr($slide['src']); ?>" type="video/<?php echo esc_attr($video_type); ?>" />
							<p class="vjs-no-js"><?php esc_html_e('Please enable JavaScript', 'fw') ?></p>
						</video>
					<?php endif; ?>
				</div>
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
