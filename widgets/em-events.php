<?php
/**
 * @author marcus
 * Standard events list widget
 */
class EM_Widget extends WP_Widget {
	
	var $defaults;
	
    /** constructor */
    function EM_Widget() {
    	$this->defaults = array(
    		'title' => __('Events','dbem'),
    		'scope' => 'future',
    		'order' => 'ASC',
    		'limit' => 5,
    		'category' => 0,
    		'format' => '<li>#_EVENTLINK<ul><li>#_EVENTDATES</li><li>#_LOCATIONTOWN</li></ul></li>',
    		'nolistwrap' => false,
    		'orderby' => 'event_start_date,event_start_time,event_name',
			'all_events' => 0,
			'all_events_text' => __('all events', 'dbem'),
			'no_events_text' => '<li>'.__('No events', 'dbem').'</li>'
    	);
		$this->em_orderby_options = apply_filters('em_settings_events_default_orderby_ddm', array(
			'event_start_date,event_start_time,event_name' => __('start date, start time, event name','dbem'),
			'event_name,event_start_date,event_start_time' => __('name, start date, start time','dbem'),
			'event_name,event_end_date,event_end_time' => __('name, end date, end time','dbem'),
			'event_end_date,event_end_time,event_name' => __('end date, end time, event name','dbem'),
		)); 
    	$widget_ops = array('description' => __( "Display a list of events on Events Manager.", 'dbem') );
        parent::WP_Widget(false, $name = 'Events', $widget_ops);	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
    	$instance = array_merge($this->defaults, $instance);
    	$instance = $this->fix_scope($instance); // depcreciate	

    	$em_category_link = null;
	    if ($instance['category'] != '0' AND strpos($instance['category'], ',') == false) {
	    	$em_category = em_get_category($instance['category']);
			$em_category_link = $em_category->get_url();
		}

    	echo $args['before_widget'];
    	if( !empty($instance['title']) ){
		    echo $args['before_title'];
		    if ($em_category_link != null) {
		    	echo '<a href="'.$em_category_link.'">';
		    }
		    echo apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
		   	if ($em_category_link != null) {
		    	echo '</a>';
		    }
		    echo $args['after_title'];
    	}
    	//remove owner searches
		$instance['owner'] = false;
		
		//legacy stuff
		//add li tags to old widgets that have no forced li wrappers
		if ( !preg_match('/^<li/i', trim($instance['format'])) ) $instance['format'] = '<li>'. $instance['format'] .'</li>';
		if (!preg_match('/^<li/i', trim($instance['no_events_text'])) ) $instance['no_events_text'] = '<li>'.$instance['no_events_text'].'</li>';
		//orderby fix for previous versions with old orderby values
		if( !array_key_exists($instance['orderby'], $this->em_orderby_options) ){
			//replace old values
			$old_vals = array(
				'name' => 'event_name',
				'end_date' => 'event_end_date',
				'start_date' => 'event_start_date',
				'end_time' => 'event_end_time',
				'start_time' => 'event_start_time'
			);
			foreach($old_vals as $old_val => $new_val){
				$instance['orderby'] = str_replace($old_val, $new_val, $instance['orderby']);
			}
		}
		
		//get events
		$events = EM_Events::get(apply_filters('em_widget_events_get_args',$instance));
		
		//output events
		echo "<ul class='events-size-".count($events)."'>";
		if ( count($events) > 0 ){
			foreach($events as $event){				
				echo $event->output( $instance['format'] );
			}
		}else{
		    echo $instance['no_events_text'];
		}
		if ( !empty($instance['all_events']) ){
			$events_link = (!empty($instance['all_events_text'])) ? em_get_link($instance['all_events_text']) : em_get_link(__('all events','dbem'));
			if ($em_category_link != null) {
		    	$events_link = '<a href="'.$em_category_link.'">'.$instance['all_events_text'].'</a>';
		    }
			echo '<li class="all-events-link">'.$events_link.'</li>';
		}
		echo "</ul>";
		
	    echo $args['after_widget'];
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
    	foreach($this->defaults as $key => $value){
    		if( !isset($new_instance[$key]) ){
    			$new_instance[$key] = $value;
    		}
    		//make sure formats and the no locations text are wrapped with li tags
		    if( ($key == 'format' || $key == 'no_events_text') && !preg_match('/^<li/i', trim($new_instance[$key])) ){
            	$new_instance[$key] = '<li>'.force_balance_tags($new_instance[$key]).'</li>';
		    }
		    //balance tags and sanitize output formats
		    if( in_array($key, array('format', 'no_events_text', 'all_events_text')) ){
		        $new_instance[$key] = force_balance_tags(wp_kses_post($new_instance[$key]));
		    }
    	}
    	return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
    	$instance = array_merge($this->defaults, $instance);
    	$instance = $this->fix_scope($instance); // depcreciate
        ?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Title', 'dbem'); ?>: </label>
			<input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('limit'); ?>"><?php esc_html_e('Number of events','dbem'); ?>: </label>
			<input type="text" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" size="3" value="<?php echo esc_attr($instance['limit']); ?>" />
		</p>
		<p>
			
			<label for="<?php echo $this->get_field_id('scope'); ?>"><?php esc_html_e('Scope','dbem'); ?>: </label><br/>
			<select id="<?php echo $this->get_field_id('scope'); ?>" name="<?php echo $this->get_field_name('scope'); ?>" class="widefat" >
				<?php foreach( em_get_scopes() as $key => $value) : ?>   
				<option value='<?php echo esc_attr($key); ?>' <?php echo ($key == $instance['scope']) ? "selected='selected'" : ''; ?>>
					<?php echo esc_html($value); ?>
				</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('order'); ?>"><?php esc_html_e('Order By','dbem'); ?>: </label>
			<select  id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>" class="widefat">
				<?php foreach($this->em_orderby_options as $key => $value) : ?>   
	 			<option value='<?php echo esc_attr($key); ?>' <?php echo ( !empty($instance['orderby']) && $key == $instance['orderby']) ? "selected='selected'" : ''; ?>>
	 				<?php echo esc_html($value); ?>
	 			</option>
				<?php endforeach; ?>
			</select> 
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('order'); ?>"><?php esc_html_e('Order','dbem'); ?>: </label>
			<select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>" class="widefat">
				<?php 
				$order_options = apply_filters('em_widget_order_ddm', array(
					'ASC' => __('Ascending','dbem'),
					'DESC' => __('Descending','dbem')
				)); 
				?>
				<?php foreach( $order_options as $key => $value) : ?>   
	 			<option value='<?php echo esc_attr($key); ?>' <?php echo ($key == $instance['order']) ? "selected='selected'" : ''; ?>>
	 				<?php echo esc_html($value); ?>
	 			</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
            <label for="<?php echo $this->get_field_id('category'); ?>"><?php esc_html_e('Category IDs','dbem'); ?>: </label>
            <input type="text" id="<?php echo $this->get_field_id('category'); ?>" class="widefat" name="<?php echo $this->get_field_name('category'); ?>" size="3" value="<?php echo esc_attr($instance['category']); ?>" /><br />
            <em><?php esc_html_e('1,2,3 or 2 (0 = all)','dbem'); ?> </em>
        </p>
        <p>
			<label for="<?php echo $this->get_field_id('all_events'); ?>"><?php esc_html_e('Show all events link at bottom?','dbem'); ?>: </label>
			<input type="checkbox" id="<?php echo $this->get_field_id('all_events'); ?>" name="<?php echo $this->get_field_name('all_events'); ?>" <?php echo (!empty($instance['all_events']) && $instance['all_events']) ? 'checked':''; ?>  class="widefat">
		</p>
		<p id="<?php echo $this->get_field_id('all_events'); ?>-section">
			<label for="<?php echo $this->get_field_id('all_events'); ?>"><?php esc_html_e('All events link text?','dbem'); ?>: </label>
			<input type="text" id="<?php echo $this->get_field_id('all_events_text'); ?>" name="<?php echo $this->get_field_name('all_events_text'); ?>" value="<?php echo esc_attr( $instance['all_events_text'] ); ?>" >
		</p>
		<script type="text/javascript">
		jQuery('#<?php echo $this->get_field_id('all_events'); ?>').change( function(){
			if( this.checked ){
			    jQuery(this).parent().next().show();
			}else{
				jQuery(this).parent().next().hide();
			} 
		}).trigger('change');
		</script>
		<em><?php echo sprintf( esc_html__('The list is wrapped in a %s tag, so if an %s tag is not wrapping the formats below it will be added automatically.','dbem'), '<code>&lt;ul&gt;</code>', '<code>&lt;li&gt;</code>'); ?></em>
        <p>
			<label for="<?php echo $this->get_field_id('format'); ?>"><?php esc_html_e('List item format','dbem'); ?>: </label>
			<textarea rows="5" cols="24" id="<?php echo $this->get_field_id('format'); ?>" name="<?php echo $this->get_field_name('format'); ?>" class="widefat"><?php echo esc_textarea($instance['format']); ?></textarea>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('no_events_text'); ?>"><?php _e('No events message','dbem'); ?>: </label>
			<input type="text" id="<?php echo $this->get_field_id('no_events_text'); ?>" name="<?php echo $this->get_field_name('no_events_text'); ?>" value="<?php echo esc_attr( $instance['no_events_text'] ); ?>" >
		</p>
        <?php 
    }
    
    /**
     * Backwards compatability for an old setting which is now just another scope.
     * @param unknown_type $instance
     * @return string
     */
    function fix_scope($instance){
    	if( !empty($instance['time_limit']) && is_numeric($instance['time_limit']) && $instance['time_limit'] > 1 ){
    		$instance['scope'] = $instance['time_limit'].'-months';
    	}elseif( !empty($instance['time_limit']) && $instance['time_limit'] == 1){
    		$instance['scope'] = 'month';
    	}elseif( !empty($instance['time_limit']) && $instance['time_limit'] == 'no-limit'){
    		$instance['scope'] = 'all';
    	}
    	return $instance;
    }
}
add_action('widgets_init', create_function('', 'return register_widget("EM_Widget");'));
?>