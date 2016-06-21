<?php
/**
 * Find template usage and update page templates
 */
class Template_Command extends WP_CLI_Command {

    private $_table_prefix = '';
    private $_wpdb;

    public function __construct() {
        $this->_wpdb = $GLOBALS['wpdb'];
        $this->_table_prefix = $GLOBALS['table_prefix'];
    }

    /**
     * List all templates used
     *
     * [--grep]
     * : filter by string pattern
     *
     * ## EXAMPLES
     *
     *     wp template list_all
     */
    public function list_all($positional_args = array(), $assoc_args = array())
    {
        $format = ! empty( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
        $filter = ! empty( $assoc_args['grep'] ) ? $assoc_args['grep'] . '%' : '';
        $table = $this->_table_prefix . 'postmeta';

        $sql = "SELECT DISTINCT `meta_value` AS `template` FROM `$table` 
             WHERE `meta_key` = '_wp_page_template'";

        if(!empty($filter)) {
            $sql .= " AND meta_value LIKE " . $this->_wpdb->prepare('%s', $filter);
        }

        $posts = $this->_wpdb->get_results($sql, ARRAY_A);

        $formatter_args = array( 'format' => $format );

        if(!empty($posts)) {
            $formatter = new \WP_CLI\Formatter( $formatter_args, array('template'));
            $formatter->display_items( $posts );
        }
        else {
            WP_CLI::warning( "No selected templates found!");
        }
    }


    /**
     * Find all posts that have a <template> selected
     *
     * ## OPTIONS
     *
     * [<template>...]
     * : template filename
     *
     * [--format]
     * : table, csv, json
     *
     * ## EXAMPLES
     *
     *     wp template find page-contact.php
     *
     * @subcommand find
     */

    public function find($positional_args, $assoc_args = array()) {

        if(empty($positional_args)) {
            WP_CLI::error( "No template specified" );
            return;
        }

        $format = ! empty( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
        $table = $this->_table_prefix . 'postmeta';
        $templates = array();

        foreach($positional_args as $template) {
            $templates[] = $this->_wpdb->prepare('%s', $template);
        }

        $sql = "SELECT `post_id`, `meta_value` AS `template` FROM `$table` 
             WHERE `meta_key` = '_wp_page_template'
               AND `meta_value` IN (" . implode(",", $templates) . ")";
        $posts = $this->_wpdb->get_results($sql, ARRAY_A);

        $formatter_args = array( 'format' => $format );

        if(!empty($posts)) {
            $formatter = new \WP_CLI\Formatter( $formatter_args, array('post_id', 'template'));
            $formatter->display_items( $posts );
        }
        else {
            WP_CLI::warning( "No posts found with template(s): " . implode(', ', $positional_args));
        }
    }

    /**
     * Changes the template for a set of posts using old template
     * or post IDs as a filter
     *
     * ## OPTIONS
     *
     * [<new-template>]
     * : Template filename that we want to assign to post(s)
     *
     * [--template]
     * : old template that we want to replace with new template filename
     *
     * [--post_id]
     * : comma separated list of post IDs
     *
     * [--limit]
     * : limit number of posts to update
     *
     * ## EXAMPLES
     *
     *     wp change page-contact-new.php template=page-contact-old.php
     *     wp change page-contact-new.php post_id=123,124
     *
     * @subcommand change
     */
    public function change($positional_args, $assoc_args = array()) {
        if(empty($positional_args)) {
            WP_CLI::error( "New template not specified. Run wp template change --help for syntax." );
            return;
        }

        $or_required = array('post_id', 'template');
        if(count($or_required) == count(array_diff($or_required, array_keys($assoc_args)))) {
            WP_CLI::error( "At least one filter is required. Run wp template change --help for syntax." );
            return;
        }

        $new_template = $this->_wpdb->prepare('%s', current($positional_args));

        // filters
        $where = "`meta_key` = '_wp_page_template'";
        if(isset($assoc_args['post_id']) && !empty($assoc_args['post_id'])) {
            $post_ids = explode(',', $assoc_args['post_id']);

            foreach($post_ids as &$post_id)
                $post_id = intval($post_id);

            $where .= ' AND `post_id` IN (' . implode(',', $post_ids) . ')';
        }

        if(isset($assoc_args['template']) && !empty($assoc_args['template'])) {
            $old_template = $this->_wpdb->prepare('%s', $assoc_args['template']);
            $where .= ' AND `meta_value` = ' . $old_template;
        }

        // limit
        $limit = "";
        if(isset($assoc_args['limit'])) {
            $limit = 'LIMIT ' . intval($assoc_args['limit']);
        }

        $sql = "UPDATE `wp_postmeta` SET `meta_value` = $new_template WHERE $where $limit";
        $result = $this->_wpdb->query($sql);

        WP_CLI::success( "Sucessfully executed. $result posts updated." );
    }


}

WP_CLI::add_command( 'template', 'Template_Command' );
