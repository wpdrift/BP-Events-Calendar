<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * BPEC_Events_Members Classes
 *
 * Handles populating and saving group event members.
 *
 * @package		BPEC/Classes/
 * @category	Class
 */

class BPEC_Events_Members {

    public $id = 0;

    public $group_id;

    public $event_id;

    public $user_id;

    public $inviter_id;

    public $status;

    /**
     * Constructor.
     *
     * @param int $event_id The ID of the event we want to join.
     * @param int $group_id The ID contain the event.
     * @param int $user_id The user ID who want to join the event
     * @param string $status The status of event joining.
     */
    public function __construct( $group_id = 0, $event_id = 0, $user_id = 0, $inviter_id = 0, $status = '' ) {
        if ( ! empty( $event_id ) && ! empty( $user_id ) ) {
            $this->group_id = (int) $group_id;
            $this->event_id  = (int) $event_id;
            $this->user_id = (int) $user_id;
            $this->inviter_id = (int) $inviter_id;
            $this->status = $status;

            $this->populate();
        }
    }

    /**
     * Populate method.
     *
     * Used in constructor.
     *
     * @since 1.0.0
     */
    protected function populate() {
        global $wpdb;

        // we always require a event ID
        if ( empty( $this->event_id ) ) {
            return;
        }

        // check cache first
        $key = "{$this->group_id}:{$this->event_id}:{$this->user_id}";
        $data = wp_cache_get( $key, 'bpec_events_members_data' );

        // Run query if no cache
        if ( false === $data ) {
            // SQL statement
            $sql =  self::get_select_sql( 'id, status' );
            $sql .= self::get_where_sql( array(
                'event_id'   => $this->event_id,
                'user_id'    => $this->user_id,
            ) );


            // Run the query
            $data = $wpdb->get_results( $sql );

            // Got a match; grab the results
            if ( ! empty( $data ) ) {
                // Select first row from result
                $data = $data[0];

                // No match; set cache to zero to prevent further hits to database
            } else {
                $data = 0;
            }

            // Set the cache
            wp_cache_set( $key, $data, 'bpec_events_members_data' );
        }

        // Populate some other properties
        if ( ! empty( $data ) ) {

            $this->id = isset($data->id) ? $data->id : '';
            if ( empty( $this->status ) &&  !empty( $data->status ) ) {
                $this->status = $data->status;
            }
        }

    }

    /**
     * Saves a event and member relationship into the database.
     *
     * @since 1.0.0
     */
    public function save() {
        global $wpdb, $bp;

        // do not use these filters
        // use the 'bpec_events_members_before_save' hook instead
        $this->group_id   = apply_filters( 'bpec_events_members_group_id_before_save', $this->group_id, $this->id );
        $this->event_id   = apply_filters( 'bpec_events_members_event_id_before_save', $this->event_id, $this->id );

        do_action_ref_array( 'bpec_events_members_before_save', array( &$this ) );



        // event ID is required
        // this allows plugins to bail out of saving a members participant
        // use hooks above to redeclare 'event_id' so it is empty if you need to bail
        if ( empty( $this->event_id ) ) {
            return false;
        }

        // update existing entry
        if ( $this->id ) {
            $result = $wpdb->query( $wpdb->prepare(
                "UPDATE {$bp->bpec->table_name_members} SET group_id = %d, event_id = %d, user_id = %d, inviter_id = %d, status = %s WHERE id = %d",
                $this->group_id,
                $this->event_id,
                $this->user_id,
                $this->inviter_id,
                $this->status,
                $this->id
            ) );

            // add new entry
        } else {
            $result = $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$bp->bpec->table_name_members} ( group_id, event_id, user_id, inviter_id, status ) VALUES ( %d, %d, %d, %d, %s )",
                $this->group_id,
                $this->event_id,
                $this->user_id,
                $this->inviter_id,
                $this->status
            ) );
            $this->id = $wpdb->insert_id;
        }

        // Save cache
        $data = new stdClass;
        $data->id = $this->id;
        wp_cache_set( "{$this->group_id}:{$this->event_id}:{$this->user_id}", $data, 'bpec_events_members_data' );

        do_action_ref_array( 'bpec_events_members_after_save', array( &$this ) );

        return $result;
    }

    /**
     * Deletes a event and members relationship from the database.
     *
     */
    public function delete() {
        global $wpdb, $bp;

        // SQL statement
        $sql  = "DELETE FROM {$bp->bpec->table_name_members} ";
        $sql .= self::get_where_sql( array(
            'id' => $this->id,
        ) );

        // Delete cache
        wp_cache_delete( "{$this->group_id}:{$this->event_id}:{$this->user_id}", 'bpec_events_members_data' );

        return $wpdb->query( $sql );
    }


    /** STATIC METHODS *****************************************************/

    /**
     * Generate the SELECT SQL statement used to query group relationships.
     *
     * @param string $column
     * @return string
     */
    protected static function get_select_sql( $column = '' ) {
        global $bp;

        return sprintf( "SELECT %s FROM %s ", esc_sql( $column ), esc_sql( $bp->bpec->table_name_members ) );
    }

    /**
     * Generate the WHERE SQL statement used to query event and group relationships.
     *
     * @param array $params
     * @return string
     */
    protected static function get_where_sql( $params = array() ) {
        global $wpdb;

        $where_conditions = array();

        if ( ! empty( $params['id'] ) ) {
            $in = implode( ',', wp_parse_id_list( $params['id'] ) );
            $where_conditions['id'] = "id IN ({$in})";
        }

        if ( ! empty( $params['group_id'] ) ) {
            $group_ids = implode( ',', wp_parse_id_list( $params['group_id'] ) );
            $where_conditions['group_id'] = "group_id IN ({$group_ids})";
        }

        if ( ! empty( $params['event_id'] ) ) {
            $event_ids = implode( ',', wp_parse_id_list( $params['event_id'] ) );
            $where_conditions['event_id'] = "event_id IN ({$event_ids})";
        }

        if ( ! empty( $params['user_id'] ) ) {
            $user_ids = implode( ',', wp_parse_id_list( $params['user_id'] ) );
            $where_conditions['user_id'] = "user_id IN ({$user_ids})";
        }

        if ( isset( $params['status'] ) ) {
            $where_conditions['status'] = $wpdb->prepare( "status = %s", $params['status'] );
        }

        return 'WHERE ' . join( ' AND ', $where_conditions );

    }

    /**
     * Generate the ORDER BY SQL statement used to query event members
     *
     * @param array $params {
     *     Array of arguments.
     *     @type string $orderby The DB column to order results by. Default: 'id'.
     *     @type string $order The order. Either 'ASC' or 'DESC'. Default: 'DESC'.
     * }
     * @return string
     */
    protected static function get_orderby_sql( $params = array() ) {
        $r = wp_parse_args( $params, array(
            'orderby' => 'id',
            'order'   => 'DESC',
        ) );

        // sanitize 'orderby' DB oclumn lookup
        switch ( $r['orderby'] ) {
            // columns available for lookup
            case 'id' :
            case 'group_id' :
            case 'event_id' :
            case 'user_id' :
            case 'status' :
                break;

            // fallback to 'id' column on anything else
            default :
                $r['orderby'] = 'id';
                break;
        }

        // only allow ASC or DESC for order
        if ( 'ASC' !== $r['order'] || 'DESC' !== $r['order'] ) {
            $r['order'] = 'DESC';
        }

        return sprintf( " ORDER BY %s %s", $r['orderby'], $r['order'] );
    }

    /**
     * Get the members IDs for a given event.
     *
     * @param int $event_id The event ID.
     * @param string $status The event join status.  Leave blank to query users.
     * @param array $query_args {
     *     Various query arguments
     *     @type string $orderby The DB column to order results by. Default: 'id'.
     *     @type string $order The order. Either 'ASC' or 'DESC'. Default: 'DESC'.
     * }
     * @return array
     */
    public static function get_members( $event_id = 0, $status = '', $query_args = array() ) {
        global $wpdb;

        // SQL statement
        $sql  = self::get_select_sql( 'user_id' );
        $sql .= self::get_where_sql( array(
            'event_id'   => $event_id,
            'status' => $status,
        ) );

        // Setup orderby query
        $orderby = array();
        if ( ! empty( $query_args['orderby'] ) ) {
            $orderby = $query_args['orderby'];
        }
        if ( ! empty( $query_args['order'] ) ) {
            $orderby = $query_args['order'];
        }
        $sql .= self::get_orderby_sql( $orderby );

        // do the query
        return $wpdb->get_col( $sql );
    }

    /**
     * Get the members count for a particular event.
     *
     * @param int    $event_id   The event ID to grab the members count for.
     * @param string $status The joining status. Leave blank to query for users.  Default: ''
     * @return int
     */
    public static function get_members_count( $event_id = 0, $status = '' ) {
        global $wpdb;

        $sql  = self::get_select_sql( 'COUNT(id)' );
        $args = array(
            'event_id'   => $event_id,
        );
        if ( ! empty( $status ) ) {
            $args['status'] = $status;
        }
        $sql .= self::get_where_sql( $args );

        return (int) $wpdb->get_var( $sql );
    }
}

/**
 * Join event
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int    $group_id
 *     @type int    $event_id
 *     @type string $user_id
 *     @type string $inviter_id
 *     @type string $status
 * }
 * @return bool
 */
function bpec_join_event( $args = '' ) {

    $r = wp_parse_args( $args, array(
        'group_id'      => 0,
        'user_id'       => bp_loggedin_user_id(),
        'inviter_id'    => 0,
        'status'        => 'interested',
    ) );

    $events_members = new BPEC_Events_Members( $r['group_id'], $r['event_id'], $r['user_id'], $r['inviter_id'], $r['status'] );

    // save!
    if ( in_array( $r['status'], array('interested', 'going') ) && $events_members->save() ) {
        do_action( 'bpec_member_joined_event', $r );
        return true;
    }

    // delete!
    if ( 'delete' == $r['status'] && $events_members->delete() ) {
        do_action( 'bpec_member_left_event', $r );
        return true;
    }

    return false;
}