<?php

/**
 * - This controller does not meant to replicate the rest api functionality
 * - Using this entries controller only for the formatted entries such as fetching the formatted entries data for displaying charts and complex filtration
 * - To simply get the entries, REST API V3 is used 
 */

class cwp_gf_Entries_Controller extends WP_REST_Controller
{

    const version = 'v1'; # controller version
    const post_type = "cwp_gf_entries";

    # Here initialize our namespace and resource name.
    public function __construct()
    {
        $this->namespace = '/gutenberg-forms/entries/' . self::version;
        $this->resource_name = 'posts'; # resources that will be utilized in this controller
    }

    /**
     * Checking some permissions.
     *
     * @param WP_REST_Request $request Current request.
     */

    public function test_permissions($request)
    {

        //TODO CHECK FOR CURRENT USER READ PERMISSION

        // if (!current_user_can('edit_post')) {
        //     return new WP_Error(
        //         'rest_forbidden',
        //         esc_html__("You do not have permission read."),
        //         array('status' => $this->authorization_status_code())
        //     );
        // }
        return true;
    }

    /**
     * Registering all necessary routes 
     */
    public function register_routes()
    {

        # registering a readable endpoint for entries

        register_rest_route($this->namespace, '/bar', array(

            array(
                'methods'   => 'GET',
                'callback'  => array($this, 'get_bar_entries_data'),
                'permission_callback' => array($this, 'test_permissions'),
            ),

            'schema' => array($this, 'get_bar_entries_schema'),

        ));
    }

    /**
     * Getting the required schema for bar chart.
     *
     * @param WP_REST_Request $request Current request.
     */

    public function get_bar_entries_schema($request)
    {

        if ($this->schema) {
            // Since WordPress 5.3, the schema can be cached in the $schema property.
            return $this->schema;
        }

        $this->schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title'                => 'post',
            'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties'           => array(
                // 'id' => array(
                //     'description'  => esc_html__('Unique identifier for the object.', 'my-textdomain'),
                //     'type'         => 'integer',
                //     'context'      => array('view', 'edit', 'embed'),
                //     'readonly'     => true,
                // ),
                'data' => array(
                    'description'  => esc_html__('Data for the bar chart.', 'my-textdomain'),
                    'type'         => 'array',
                ),
            ),
        );

        return $this->schema;
    }

    /**
     * Fetching the entries bar chart data
     */

    public function get_bar_entries_data()
    {
        $period = 7; # default period is last 7 days

        # fetching the post of last $period days separately        

        $submissions = [];
        $forms = [];
        $data = [];
        $labels = [];
        $heighest_entry = 0;


        for ($day = 1; $day <= $period; ++$day) {

            $time = '-' . (string)$day + 1 . ' days';
            $forms_in_this_day = [];
            $date = date('Y-m-d', strtotime($time));

            $args = array(
                'post_type' => self::post_type,
                'order' => 'DESC',
                'date_query' => array(
                    'after'  => $date,
                    'before' => $date, # limiting it to only get 1 day posts
                    'inclusive' => true,
                )
            );

            $entries = get_posts($args);

            foreach ($entries as $key => $entry_post) {
                $form_id = get_post_meta($entry_post->ID, 'form_id__' . self::post_type, true);
                $extra_meta = get_post_meta($entry_post->ID, 'extra__' . self::post_type, false);
                $form_label = $extra_meta[0]['form_label'];

                if (!in_array($form_id, $forms)) {
                    $forms[] = $form_id;
                }

                if (!in_array($form_id, $forms_in_this_day)) {
                    $forms_in_this_day[$form_id] = [
                        'label'             => $form_label,
                        'total_submissions' => $this->get_entries_counts_with_specific_form($form_id, $date, $date)
                    ];
                }

                if (!in_array($form_label, $labels)) {
                    $labels[] = $form_label;
                }
            }

            $total_count = self::count($args);

            if ($total_count > $heighest_entry) {
                $heighest_entry = $total_count;
            }


            $data[$day] = [
                'total_count' => $total_count,
                'total_forms' => $forms_in_this_day,
                'date'        => $date
            ];
        }

        return [
            'data'  => $data,
            'forms' => $forms,
            'labels'  => $labels,
            'highest_form_entry'     => $heighest_entry
        ];
    }

    /**
     * Will return total count of matching filter
     * @param array filter that will be passed in WP_Query
     * @return int total count
     */

    public static function count($args)
    {
        $query = new WP_Query($args);
        $count = $query->found_posts;
        return (int)$count;
    }

    /**
     * Will return total entries count of specific form between (inclusive) the starting and ending date given
     * @param string $form_id The gutenberg form id
     * @param date $start starting from certain date
     * @param date $end ending on this date 
     * @return int 
     */

    public function get_entries_counts_with_specific_form($form_id, $start, $end)
    {
        $args = array(
            'post_type' => self::post_type,
            'order' => 'DESC',
            'date_query' => array(
                'after'  => $start,
                'before' => $end,
                'inclusive' => true,
            ),
            'meta_query' => array(
                array(
                    'key'       => 'form_id__' . self::post_type,
                    'value'     => $form_id,
                )
            )
        );

        $total_submissions = self::count($args);

        return $total_submissions;
    }


    /**
     * @return {Int} current authorization status code
     */

    public function authorization_status_code()
    {

        $status = 401;

        if (is_user_logged_in()) {
            $status = 403;
        }

        return $status;
    }
}