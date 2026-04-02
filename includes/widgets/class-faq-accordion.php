<?php

namespace ECFW\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Accordion extends Widget_Base
{
    public function get_name()
    {
        return 'ecfw-faq-accordion';
    }

    public function get_title()
    {
        return __('FAQ Accordion (CPT)', 'elementor-cpt-faq-widget');
    }

    public function get_icon()
    {
        return 'eicon-accordion';
    }

    public function get_categories()
    {
        return array('general');
    }

    public function get_keywords()
    {
        return array('faq', 'accordion', 'cpt');
    }

    public function get_style_depends()
    {
        return array('ecfw-accordion');
    }

    public function get_script_depends()
    {
        return array('ecfw-accordion');
    }

    private function get_faq_taxonomy_options()
    {
        $options = array();
        $taxonomies = get_object_taxonomies('ecfw_faq', 'objects');

        if (!is_array($taxonomies)) {
            return $options;
        }

        foreach ($taxonomies as $taxonomy => $taxonomy_object) {
            if (!isset($taxonomy_object->labels->singular_name)) {
                continue;
            }
            $options[$taxonomy] = $taxonomy_object->labels->singular_name;
        }

        return $options;
    }

    private function get_faq_term_options($taxonomy)
    {
        $options = array();

        if (!taxonomy_exists($taxonomy)) {
            return $options;
        }

        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));

        if (is_wp_error($terms) || empty($terms)) {
            return $options;
        }

        foreach ($terms as $term) {
            $options[(string) $term->term_id] = $term->name;
        }

        return $options;
    }

    private function get_faq_post_options()
    {
        $options = array();
        $faq_ids = get_posts(array(
            'post_type' => 'ecfw_faq',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ));

        if (empty($faq_ids)) {
            return $options;
        }

        foreach ($faq_ids as $faq_id) {
            $title = get_the_title($faq_id);
            if ($title === '') {
                $title = sprintf(__('FAQ #%d', 'elementor-cpt-faq-widget'), $faq_id);
            }
            $options[(string) $faq_id] = $title;
        }

        return $options;
    }

    private function get_taxonomy_terms_control_id($taxonomy)
    {
        return 'faq_terms_' . str_replace('-', '_', sanitize_key($taxonomy));
    }

    protected function register_controls()
    {
        $taxonomy_options = $this->get_faq_taxonomy_options();
        $default_taxonomy = '';

        if (!empty($taxonomy_options)) {
            $taxonomy_keys = array_keys($taxonomy_options);
            $default_taxonomy = reset($taxonomy_keys);
        }

        $this->start_controls_section(
            'section_query',
            array(
                'label' => __('Query', 'elementor-cpt-faq-widget'),
            )
        );

        $this->add_control(
            'query_source',
            array(
                'label' => __('Source', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => array(
                    'all' => __('All FAQs', 'elementor-cpt-faq-widget'),
                    'taxonomy' => __('Taxonomy', 'elementor-cpt-faq-widget'),
                    'manual' => __('Manual Selection', 'elementor-cpt-faq-widget'),
                ),
            )
        );

        if (!empty($taxonomy_options)) {
            $this->add_control(
                'faq_taxonomy',
                array(
                    'label' => __('Taxonomy', 'elementor-cpt-faq-widget'),
                    'type' => Controls_Manager::SELECT,
                    'options' => $taxonomy_options,
                    'default' => $default_taxonomy,
                    'condition' => array(
                        'query_source' => 'taxonomy',
                    ),
                )
            );

            foreach ($taxonomy_options as $taxonomy_slug => $taxonomy_label) {
                $terms_control_id = $this->get_taxonomy_terms_control_id($taxonomy_slug);

                // Only attempt to fetch terms for taxonomies that actually exist.
                $term_options = array();
                $term_description = __('Leave empty to show all FAQs assigned to this taxonomy.', 'elementor-cpt-faq-widget');
                if (taxonomy_exists($taxonomy_slug)) {
                    $term_options = $this->get_faq_term_options($taxonomy_slug);
                } else {
                    /* translators: %s: taxonomy slug */
                    $term_description = sprintf(__('Taxonomy "%s" is not registered. Terms cannot be loaded.', 'elementor-cpt-faq-widget'), $taxonomy_slug) . ' ' . $term_description;
                }

                $this->add_control(
                    $terms_control_id,
                    array(
                        'label' => sprintf(__('Terms (%s)', 'elementor-cpt-faq-widget'), $taxonomy_label),
                        'type' => Controls_Manager::SELECT2,
                        'multiple' => true,
                        'label_block' => true,
                        'options' => $term_options,
                        'condition' => array(
                            'query_source' => 'taxonomy',
                            'faq_taxonomy' => $taxonomy_slug,
                        ),
                        'description' => $term_description,
                    )
                );
            }
        } else {
            $this->add_control(
                'faq_taxonomy_notice',
                array(
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('No taxonomies are assigned to the FAQ post type.', 'elementor-cpt-faq-widget'),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                    'condition' => array(
                        'query_source' => 'taxonomy',
                    ),
                )
            );
        }

        $this->add_control(
            'manual_faq_ids',
            array(
                'label' => __('Select FAQs', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'label_block' => true,
                'options' => $this->get_faq_post_options(),
                'condition' => array(
                    'query_source' => 'manual',
                ),
                'description' => __('Order of selection is preserved in the accordion output.', 'elementor-cpt-faq-widget'),
            )
        );

        $this->add_control(
            'posts_per_page',
            array(
                'label' => __('Number of FAQs', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'default' => 10,
                'condition' => array(
                    'query_source!' => 'manual',
                ),
            )
        );

        $this->add_control(
            'orderby',
            array(
                'label' => __('Order By', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => array(
                    'date' => __('Date', 'elementor-cpt-faq-widget'),
                    'title' => __('Title', 'elementor-cpt-faq-widget'),
                    'menu_order' => __('Menu Order', 'elementor-cpt-faq-widget'),
                    'rand' => __('Random', 'elementor-cpt-faq-widget'),
                ),
                'condition' => array(
                    'query_source!' => 'manual',
                ),
            )
        );

        $this->add_control(
            'order',
            array(
                'label' => __('Order', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => array(
                    'ASC' => __('ASC', 'elementor-cpt-faq-widget'),
                    'DESC' => __('DESC', 'elementor-cpt-faq-widget'),
                ),
                'condition' => array(
                    'query_source!' => 'manual',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_display',
            array(
                'label' => __('Display', 'elementor-cpt-faq-widget'),
            )
        );

        $this->add_control(
            'open_first',
            array(
                'label' => __('Open First Item', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'label_on' => __('Yes', 'elementor-cpt-faq-widget'),
                'label_off' => __('No', 'elementor-cpt-faq-widget'),
                'return_value' => 'yes',
            )
        );

        $this->add_control(
            'animation_duration',
            array(
                'label' => __('Animation Duration (ms)', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 2000,
                'step' => 50,
                'default' => 200,
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_icon',
            array(
                'label' => __('Icon', 'elementor-cpt-faq-widget'),
            )
        );

        $this->add_control(
            'icon_position',
            array(
                'label' => __('Position', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => array(
                    'left' => __('Left', 'elementor-cpt-faq-widget'),
                    'right' => __('Right', 'elementor-cpt-faq-widget'),
                ),
            )
        );

        $this->add_control(
            'icon_rotate',
            array(
                'label' => __('Rotate On Active', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'label_on' => __('Yes', 'elementor-cpt-faq-widget'),
                'label_off' => __('No', 'elementor-cpt-faq-widget'),
                'return_value' => 'yes',
            )
        );

        $this->add_control(
            'icon_rotate_angle',
            array(
                'label' => __('Rotate Angle (deg)', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('deg'),
                'range' => array(
                    'deg' => array('min' => 0, 'max' => 360),
                ),
                'default' => array(
                    'size' => 180,
                    'unit' => 'deg',
                ),
                'condition' => array(
                    'icon_rotate' => 'yes',
                ),
            )
        );

        $this->add_control(
            'icon',
            array(
                'label' => __('Icon', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::ICONS,
                'default' => array(
                    'value' => 'fas fa-plus',
                    'library' => 'fa-solid',
                ),
            )
        );

        $this->add_control(
            'icon_active',
            array(
                'label' => __('Active Icon', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::ICONS,
                'default' => array(
                    'value' => 'fas fa-minus',
                    'library' => 'fa-solid',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_title',
            array(
                'label' => __('Title', 'elementor-cpt-faq-widget'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->start_controls_tabs('tabs_title_style');

        $this->start_controls_tab(
            'tab_title_normal',
            array(
                'label' => __('Normal', 'elementor-cpt-faq-widget'),
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label' => __('Text Color', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-title' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'title_background',
            array(
                'label' => __('Background Color', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-title' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'title_border',
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-tab-title',
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'title_box_shadow',
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-tab-title',
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_title_active',
            array(
                'label' => __('Active', 'elementor-cpt-faq-widget'),
            )
        );

        $this->add_control(
            'title_color_active',
            array(
                'label' => __('Text Color', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-title.elementor-active' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'title_background_active',
            array(
                'label' => __('Background Color', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-title.elementor-active' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'title_border_active',
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-tab-title.elementor-active',
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'title_box_shadow_active',
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-tab-title.elementor-active',
            )
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-tab-title',
            )
        );

        $this->add_control(
            'title_padding',
            array(
                'label' => __('Padding', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'title_border_radius',
            array(
                'label' => __('Border Radius', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_content',
            array(
                'label' => __('Content', 'elementor-cpt-faq-widget'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'content_color',
            array(
                'label' => __('Text Color', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-content' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'content_background',
            array(
                'label' => __('Background Color', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-content' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-tab-content',
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'content_border',
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-tab-content',
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'content_box_shadow',
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-tab-content',
            )
        );

        $this->add_control(
            'content_padding',
            array(
                'label' => __('Padding', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'content_border_radius',
            array(
                'label' => __('Border Radius', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_icon',
            array(
                'label' => __('Icon', 'elementor-cpt-faq-widget'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->start_controls_tabs('tabs_icon_style');

        $this->start_controls_tab(
            'tab_icon_normal',
            array(
                'label' => __('Normal', 'elementor-cpt-faq-widget'),
            )
        );

        $this->add_control(
            'icon_color',
            array(
                'label' => __('Color', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-accordion-icon' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_icon_active',
            array(
                'label' => __('Active', 'elementor-cpt-faq-widget'),
            )
        );

        $this->add_control(
            'icon_color_active',
            array(
                'label' => __('Color', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-tab-title.elementor-active .elementor-accordion-icon' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'icon_size',
            array(
                'label' => __('Size', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range' => array(
                    'px' => array('min' => 8, 'max' => 48),
                    'em' => array('min' => 0.5, 'max' => 3),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-accordion-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ecfw-accordion .elementor-accordion-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ecfw-accordion .elementor-accordion-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'icon_spacing',
            array(
                'label' => __('Spacing', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 40),
                    'em' => array('min' => 0, 'max' => 2),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-accordion-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ecfw-accordion.elementor-accordion-icon-right .elementor-accordion-icon' => 'margin-right: 0; margin-left: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_spacing',
            array(
                'label' => __('Spacing', 'elementor-cpt-faq-widget'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'columns',
            array(
                'label' => __('Columns', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => '1',
                'tablet_default' => '1',
                'mobile_default' => '1',
                'options' => array(
                    '1' => __('1 Column', 'elementor-cpt-faq-widget'),
                    '2' => __('2 Columns', 'elementor-cpt-faq-widget'),
                    '3' => __('3 Columns', 'elementor-cpt-faq-widget'),
                    '4' => __('4 Columns', 'elementor-cpt-faq-widget'),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion' => '--ecfw-columns: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'item_border',
                'label' => __('Item Border', 'elementor-cpt-faq-widget'),
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-accordion-item',
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'item_box_shadow',
                'label' => __('Item Box Shadow', 'elementor-cpt-faq-widget'),
                'selector' => '{{WRAPPER}} .ecfw-accordion .elementor-accordion-item',
            )
        );

        $this->add_control(
            'item_border_radius',
            array(
                'label' => __('Item Border Radius', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion .elementor-accordion-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'items_gap',
            array(
                'label' => __('Item Gap', 'elementor-cpt-faq-widget'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 40),
                    'em' => array('min' => 0, 'max' => 2),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ecfw-accordion' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $accordion_id = 'ecfw-accordion-' . $this->get_id();
        $animation_duration = isset($settings['animation_duration']) ? (int) $settings['animation_duration'] : 200;
        $icon_position = isset($settings['icon_position']) ? $settings['icon_position'] : 'left';
        $rotate_icon = isset($settings['icon_rotate']) && $settings['icon_rotate'] === 'yes';
        $rotate_angle = 180;
        if ($rotate_icon && !empty($settings['icon_rotate_angle']['size'])) {
            $rotate_angle = (int) $settings['icon_rotate_angle']['size'];
        }

        $icon_html = '';
        $icon_active_html = '';
        if (!empty($settings['icon']['value'])) {
            ob_start();
            Icons_Manager::render_icon($settings['icon'], array('aria-hidden' => 'true'));
            $icon_html = ob_get_clean();
        }
        if (!empty($settings['icon_active']['value'])) {
            ob_start();
            Icons_Manager::render_icon($settings['icon_active'], array('aria-hidden' => 'true'));
            $icon_active_html = ob_get_clean();
        }
        if ($rotate_icon) {
            $icon_active_html = $icon_html;
        }
        if ($icon_active_html === '') {
            $icon_active_html = $icon_html;
        }

        $allowed_orderby = array('date', 'title', 'menu_order', 'rand');
        $orderby = isset($settings['orderby']) ? $settings['orderby'] : 'date';
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'date';
        }

        $order = (isset($settings['order']) && $settings['order'] === 'ASC') ? 'ASC' : 'DESC';
        $query_source = isset($settings['query_source']) ? $settings['query_source'] : 'all';

        $query_args = array(
            'post_type' => 'ecfw_faq',
            'post_status' => 'publish',
            'posts_per_page' => isset($settings['posts_per_page']) ? max(1, (int) $settings['posts_per_page']) : 10,
            'orderby' => $orderby,
            'order' => $order,
            'no_found_rows' => true,
        );

        if ($query_source === 'manual') {
            $manual_faq_ids = isset($settings['manual_faq_ids']) ? (array) $settings['manual_faq_ids'] : array();
            $manual_faq_ids = array_values(array_filter(array_unique(array_map('intval', $manual_faq_ids))));

            if (!empty($manual_faq_ids)) {
                $query_args['post__in'] = $manual_faq_ids;
                $query_args['posts_per_page'] = count($manual_faq_ids);
                $query_args['orderby'] = 'post__in';
                unset($query_args['order']);
            } else {
                $query_args['post__in'] = array(0);
            }
        } elseif ($query_source === 'taxonomy') {
            $taxonomy_options = $this->get_faq_taxonomy_options();
            $selected_taxonomy = isset($settings['faq_taxonomy']) ? sanitize_key($settings['faq_taxonomy']) : '';

            if ($selected_taxonomy !== '' && isset($taxonomy_options[$selected_taxonomy])) {
                // If the taxonomy has been renamed/removed, avoid building a
                // tax_query that could trigger errors — just skip filtering.
                if (!taxonomy_exists($selected_taxonomy)) {
                    // Log for debugging when available.
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf('ecfw: selected taxonomy "%s" not registered; skipping taxonomy filter.', $selected_taxonomy));
                    }
                } else {
                    $terms_control_id = $this->get_taxonomy_terms_control_id($selected_taxonomy);
                    $selected_terms = isset($settings[$terms_control_id]) ? (array) $settings[$terms_control_id] : array();
                    $selected_terms = array_values(array_filter(array_unique(array_map('intval', $selected_terms))));

                    if (!empty($selected_terms)) {
                        $query_args['tax_query'] = array(
                            array(
                                'taxonomy' => $selected_taxonomy,
                                'field' => 'term_id',
                                'terms' => $selected_terms,
                            ),
                        );
                    } else {
                        $query_args['tax_query'] = array(
                            array(
                                'taxonomy' => $selected_taxonomy,
                                'operator' => 'EXISTS',
                            ),
                        );
                    }
                }
            }
        }

        $query = new \WP_Query($query_args);

        if (!$query->have_posts()) {
            if (class_exists('\\Elementor\\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="ecfw-empty">' . esc_html__('No FAQs found.', 'elementor-cpt-faq-widget') . '</div>';
            }
            wp_reset_postdata();
            return;
        }

        $schema_entities = array();
        $index = 0;

        $accordion_classes = 'elementor-accordion ecfw-accordion';
        if ($icon_position === 'right') {
            $accordion_classes .= ' elementor-accordion-icon-right';
        } else {
            $accordion_classes .= ' elementor-accordion-icon-left';
        }
        if ($rotate_icon) {
            $accordion_classes .= ' ecfw-icon-rotate';
        }

        $accordion_style = '';
        if ($rotate_icon) {
            $accordion_style = ' style="--ecfw-icon-rotate:' . esc_attr($rotate_angle) . 'deg; --ecfw-icon-rotate-duration:' . esc_attr($animation_duration) . 'ms;"';
        }

        echo '<div class="' . esc_attr($accordion_classes) . '" data-accordion-id="' . esc_attr($accordion_id) . '" data-animation-duration="' . esc_attr($animation_duration) . '"' . $accordion_style . '>';

        while ($query->have_posts()) {
            $query->the_post();
            $index++;

            $question = get_the_title();
            $content = apply_filters('the_content', get_the_content());
            $content_text = wp_strip_all_tags($content);

            $schema_entities[] = array(
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $content_text,
                ),
            );

            $tab_id = $accordion_id . '-tab-' . $index;
            $content_id = $accordion_id . '-content-' . $index;
            $is_open = ($index === 1 && isset($settings['open_first']) && $settings['open_first'] === 'yes');

            echo '<div class="elementor-accordion-item">';
            echo '<div class="elementor-tab-title' . ($is_open ? ' elementor-active' : '') . '" id="' . esc_attr($tab_id) . '" role="button" aria-controls="' . esc_attr($content_id) . '" aria-expanded="' . ($is_open ? 'true' : 'false') . '">';
            echo '<span class="elementor-accordion-icon elementor-accordion-icon-closed" aria-hidden="true">' . $icon_html . '</span>';
            echo '<span class="elementor-accordion-icon elementor-accordion-icon-opened" aria-hidden="true">' . $icon_active_html . '</span>';
            echo '<span class="elementor-accordion-title">' . esc_html($question) . '</span>';
            echo '</div>';
            echo '<div class="elementor-tab-content' . ($is_open ? ' elementor-active' : '') . '" id="' . esc_attr($content_id) . '" role="region" aria-labelledby="' . esc_attr($tab_id) . '"' . ($is_open ? '' : ' hidden') . '>';
            echo wp_kses_post($content);
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';

        wp_reset_postdata();

        if (!empty($schema_entities)) {
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $schema_entities,
            );

            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
        }
    }
}
