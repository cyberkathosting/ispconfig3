<?php

class plugin_base {
	
	var $plugin_name;
	var $options;
	
	function onLoad() {
	
	}
	
	function onShow() {
	
	}
	
	function onInsert() {
	
	}
	
	function onUpdate() {
	
	}
	
	function onDelete() {
	
	}
	
	function setOptions($plugin_name, $options) {
		$this->options = $options;
		$this->plugin_name = $plugin_name;
	}

}

?>