<?php
/*
Plugin Name: Disable Specific XML RPC Methods
Plugin URI: http://leondoornkamp.nl
Description: If you want to enable the WordPress, Movable Type, MetaWeblog and Blogger XML-RPC publishing protocols, but this allows you to only enable the specific methods you need. You can disable any other methods and make your blog less vulnerable for hackers/spammers.
Author: Leon Doornkamp
Version: 1.0
Author URI: http://leondoornkamp.nl
*/


class dsxmlrpc
{
	
	function __construct()
	{
		
		$this->options = get_option( 'dsxmlrpcData' );
		
		$this->wp_page_name = 'dsxmlrpc-settings';
		
		add_filter('plugin_action_links', array( $this, 'dsxmlrpc_action_link' ), 10, 2);
		
		add_filter( 'xmlrpc_methods', array( &$this, 'remove_xmlrpc_methods' ) );
		
		$this->functions = $this->get_functions();
		
		add_action( 'admin_init', array( &$this, 'dsxmlrpc_init' ) );
		
		add_action( 'admin_menu', array( &$this, 'dsxmlrpc_admin_menu' ) );

	}
	
	function dsxmlrpc()
	{
		$this->__construct();
	}
	
	function dsxmlrpc_init()
	{

		$this->nonce = wp_create_nonce( $this->wp_page_name );
		
		register_setting( 'dsxmlrpcSettings', 'dsxmlrpcData' );
	}
	
	function dsxmlrpc_admin_menu()
	{
		add_options_page( 'Disable XML-RPC methods', 'Disable XML-RPC methods', 'administrator', $this->wp_page_name, array( $this, 'dsxmlrpc_options_page' ) );
	}
	
	function remove_xmlrpc_methods( $methods )
	{
		if( $methods ){
			foreach( $this->options as $name => $on ){
				if( $on == 'on' && isset( $methods[$name] ) )
					unset( $methods[$name] );
			}
		}
		
		return $methods;
	}
	
	function check_nonce()
	{
		$url_nonce = $_REQUEST['nonce'];

		if ( !wp_verify_nonce($url_nonce, $this->wp_page_name ) ) die("Security check. Not authorized to enable/disable XML-RPC!");
		
		return true;
	}
	
	function dsxmlrpc_options_page()
	{
		$plugin_admin_url = get_bloginfo('wpurl') . $_SERVER['PHP_SELF'] . '?page=' . $this->wp_page_name;
		
		if( isset( $_REQUEST['action'] ) ){
			switch( $_REQUEST['action'] ){
				case 'activate_xmlrpc':
					if( $this->check_nonce() )
					update_option( 'enable_xmlrpc', '1' );	
				break;
				case 'disable_xmlrpc':
					if( $this->check_nonce() )
						update_option( 'enable_xmlrpc', '' );	
				break;
			}
		}
		
?>
		<div class="wrap">
		
		<script language="javascript">
		function checkAll(type){
			jQuery('input:checkbox').each( function() {
				this.checked = 'checked';
			});
		}
		function uncheckAll(type){
			jQuery('input:checkbox').each( function() {
				this.checked = '';
			});
		}
		</script>
		
		<?php if( get_option( 'enable_xmlrpc' ) != '1' ){ ?>
			<div class="error"><p>XML-RPC publishing protocols are not enabled on your blog. These settings currently do not have any effect. Click <a href="<?php echo $plugin_admin_url . '&action=activate_xmlrpc&nonce=' . $this->nonce?>" title="Activate XMLRPC settings">here</a> to activate it.</p></div>
		<?php } else {?>
			<div class="updated"><p>XML-RPC publishing protocols are enabled on your blog. Click <a href="<?php echo $plugin_admin_url . '&action=disable_xmlrpc&nonce=' . $this->nonce?>" title="Disable XMLRPC">here</a> to disable it.</p></div>			
		<?php } ?>	
			
		<form method="post" action="<?php bloginfo( 'wpurl' )?>/wp-admin/options.php" name="dsxmlrpcSettings">
<?php
		settings_fields('dsxmlrpcSettings');
?>		
		<div id="icon-options-general" class="icon32"><br></div>
		<h2><?php echo 'Disable XML-RPC Methods' ?></h2>

		<p class="description">
			Use this to disable specific XML-RPC methods. You can use this if you want some methods to be enabled, but for security reasons you may not want all methods to be enabled. Select the methods you do NOT want to be enabled.
			More info on these methods can be found in the <a href="http://codex.wordpress.org/XML-RPC_Support" title="Wordpress codex">Wordpress Codex</a>.
			</p>
		<a class="button" href="javascript:void(0);" onclick="checkAll()" >Check all</a>
		<a class="button" href="javascript:void(0);" onclick="uncheckAll()" >Uncheck all</a>
		<input type="submit" name="submit" id="submit" value="<?php _e( 'Save Changes' )?>" class="button-primary" />
		<br/>
		

		
		<?php foreach( $this->functions as $platform => $platformFunctions ){ ?>
		<div style="float:left;width:45%">
		<h2><?php echo $platform?> functions</h2>
		<table class="widefat" style="width:95%">
			<thead>
				<tr>
					<th scope="col" width="5%">						
					</th>
					<th scope="col" width="20%">
						Function name
					</th>
					<th scope="col">
						Description
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $platformFunctions as $name => $description ){?>
				<tr valign="top">
					<th scope="row">
						<input name="dsxmlrpcData[<?php echo $name?>]" id="<?php echo $name?>" <?php $this->checked( $name )?> type="checkbox" />
					</th>
					<td>
						<label for="<?php echo $name?>"><?php echo $name?></label>
					</td>
					<td>
						<span class="description"><?php echo $description?></span>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<td></td>
					<td>
						<input type="submit" name="submit" id="submit" value="<?php _e( 'Save Changes' )?>" class="button-primary" />
					</td>
				</tr>
			</tbody>
		</table>
	</div>
		<?php } ?>
		
		</form>
		</div>
<?php
	}
	
	function checked( $value )
	{
		if( isset( $this->options[$value] ) && $this->options[$value] == 'on' )
			echo 'checked="checked"';
		
		return;
	}
	
	function dsxmlrpc_action_link($links, $file) {
    	static $this_plugin;

   		if (!$this_plugin) {
        	$this_plugin = plugin_basename(__FILE__);
    	}

    	if ($file == $this_plugin) {
        	$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=' . $this->wp_page_name . '">Settings</a>';
        	array_unshift($links, $settings_link);
    	}

    	return $links;
	}
	
	function get_functions()
	{
		return array(
			'wordpress' => array(
				'wp.getUsersBlogs'  		=> 'Retrieve the blogs of the users',
				'wp.getTags'				=> 'Get list of all tags',
				'wp.getCommentCount'		=> 'Retrieve comment count for a specific post',
				'wp.getPostStatusList'		=> 'Retrieve post statuses',
				'wp.getPageStatusList'		=> 'Retrieve all of the WordPress supported page statuses',
				'wp.getPageTemplates'		=> 'Retrieve page templates',
				'wp.getOptions'				=> 'Retrieve blog options',
				'wp.setOptions'				=> 'Update blog options',
				'wp.deleteComment'			=> 'Remove comment',
				'wp.editComment'			=> 'Edit comment',
				'wp.newComment'				=> 'Create new comment',
				'wp.getCommentStatusList'	=> 'Retrieve all of the comment status',
				'wp.getPage'				=> 'Get the page identified by the page id',
				'wp.getPages'				=> 'Get an array of all the pages on a blog',
				'wp.getPageList'			=> 'Get an array of all the pages on a blog (minimum details)',
				'wp.newPage'				=> 'Create a new page',
				'wp.deletePage'				=> 'Removes a page from the blog',
				'wp.editPage'				=> 'Make changes to a blog page',
				'wp.getAuthors'				=> 'Get an array of users for the blog',
				'wp.getCategories'			=> 'Get an array of available categories on a blog',
				'wp.newCategory'			=> 'Create a new category',
				'wp.deleteCategory'			=> 'Delete a category',
				'wp.suggestCategories'		=> 'Get an array of categories that start with a given string',
				'wp.uploadFile'				=> 'Upload a file',
				'wp.getComment'				=> 'Gets a comment',
				'wp.getComments'			=> 'Gets a set of comments for a given post',
				'wp.getPostFormats'			=> 'Get the post formats',
				'wp.getMediaLibrary'		=> 'Get the media Library',
				'wp.getMediaItem'			=> 'Get a media item'
				),
			'blogger' => array(
				'blogger.deletePost' 		=> '',
				'blogger.editPost'			=> '',
				'blogger.newPost'			=> '',
				'blogger.setTemplate'		=> '',
				'blogger.getTemplate'		=> '',
				'blogger.getRecentPosts'	=> '',
				'blogger.getPost'			=> '',
				'blogger.getUserInfo'		=> '',
				'blogger.getUsersBlogs'		=> ''
			),
			'metaweblog' => array(
				'metaWeblog.getUsersBlogs'	=> '',
				'metaWeblog.setTemplate'	=> '',
				'metaWeblog.getTemplate'	=> '',
				'metaWeblog.deletePost'		=> '',
				'metaWeblog.newMediaObject'	=> '',
				'metaWeblog.getCategories'	=> '',
				'metaWeblog.getRecentPosts'	=> '',
				'metaWeblog.getPost'		=> '',
				'metaWeblog.editPost'		=> '',
				'metaWeblog.newPost'		=> ''
			),
			'movabletype' => array(
				'mt.publishPost'			=> '',
				'mt.getTrackbackPings'		=> '',
				'mt.supportedTextFilters'	=> '',
				'mt.supportedMethods'		=> '',
				'mt.setPostCategories'		=> '',
				'mt.getPostCategories'		=> '',
				'mt.getRecentPostTitles'	=> '',
				'mt.getCategoryList'		=> ''
			),
			'pingback' => array(
				'pingback.extensions.getPingbacks'	=> '',
				'pingback.ping'						=> ''
			),
			'demo' => array(
				'demo.addTwoNumbers'	=> '',
				'demo.sayHello'			=> ''
			)
		);
	}
}

$dsxmlrpc = new dsxmlrpc();

?>