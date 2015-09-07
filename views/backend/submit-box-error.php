<?php if (!defined('FW')) die('Forbidden'); ?>
<div class="submitbox" id="submitpost">
	<div id="major-publishing-actions">
		<div id="delete-action">
			<?php
			if (current_user_can("delete_post", $post->ID)) {
				if (!EMPTY_TRASH_DAYS)
					$delete_text = __('Delete Permanently', 'fw');
				else
					$delete_text = __('Move to Trash', 'fw');
				?>
				<a class="submitdelete deletion"
				   href="<?php echo esc_attr(get_delete_post_link($post->ID)); ?>"><?php echo $delete_text; ?></a><?php
			} ?>
		</div>
		<div class="clear"></div>
	</div>
</div>