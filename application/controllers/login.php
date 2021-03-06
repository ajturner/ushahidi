<?php defined('SYSPATH') or die('No direct script access.');
/**
 * This controller handles login requests.
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     Login Controller  
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

class Login_Controller extends Template_Controller {
	
    public $auto_render = TRUE;
	
    protected $user;
	
    // Session Object
    protected $session;
	
    // Main template
    public $template = 'admin/login';
	

    public function __construct()
    {
        parent::__construct();
		
        $this->session = new Session();
		// $profiler = new Profiler;
    }
	
    public function index()
    {
        $auth = Auth::instance();
		
        // If already logged in redirect to user account page
        // Otherwise attempt to auto login if autologin cookie can be found
        // (Set when user previously logged in and ticked 'stay logged in')
        if ($auth->logged_in() OR $auth->auto_login())
        {
            if ($user = Session::instance()->get('auth_user',FALSE))
            {
                url::redirect('admin/dashboard');
            }
        }
				
		
        $form = array(
	        'username' => '',
	        'password' => '',
                );

        //  copy the form as errors, so the errors will be stored with keys corresponding to the form field names
        $errors = $form;
        $form_error = FALSE;
		
		
        // Set up the validation object
        $_POST = Validation::factory($_POST)
            ->pre_filter('trim')
            ->add_rules('username', 'required')
            ->add_rules('password', 'required');
		
        if ($_POST->validate())
        {
            // Sanitize $_POST data removing all inputs without rules
            $postdata_array = $_POST->safe_array();

            // Load the user
            $user = ORM::factory('user', $postdata_array['username']);

            // If no user with that username found
            if (! $user->id)
            {
                $_POST->add_error('username', 'login error');
            }
            else
            {
                $remember = (isset($_POST['remember']))? TRUE : FALSE;

                // Attempt a login
                if ($auth->login($user, $postdata_array['password'], $remember))
                {
                    url::redirect('admin/dashboard');
                }
                else
                {
                    $_POST->add_error('password', 'login error');
                }
            }
            // repopulate the form fields
            $form = arr::overwrite($form, $_POST->as_array());
			
            // populate the error fields, if any
            // We need to already have created an error message file, for Kohana to use
            // Pass the error message file name to the errors() method			
            $errors = arr::overwrite($errors, $_POST->errors('auth'));
            $form_error = TRUE;
        }
		
        $this->template->errors = $errors;
        $this->template->form = $form;
        $this->template->form_error = $form_error;
    }
    
    /**
     * Reset password upon user request.
     */
    public function resetpassword()
    {
    	$auth = Auth::instance();
		
        // If already logged in redirect to user account page
        // Otherwise attempt to auto login if autologin cookie can be found
        // (Set when user previously logged in and ticked 'stay logged in')
        if ($auth->logged_in() OR $auth->auto_login())
        {
            if ($user = Session::instance()->get('auth_user',FALSE))
            {
                url::redirect('admin/dashboard');
            }
        }
    	
    	$this->template = new View('admin/reset_password');
		
		$this->template->title = 'Password Reset';
		$form = array
	    (
	        //'user_id'   => '',
			'resetemail' 	=> '',
	    );
		
		//  copy the form as errors, so the errors will be stored with keys corresponding to the form field names
	    $errors = $form;
		$form_error = FALSE;
		$form_saved = FALSE;
		$form_action = "";

		// check, has the form been submitted, if so, setup validation
	    if ($_POST)
	    {
	        $post = Validation::factory($_POST);

	         //  Add some filters
	        $post->pre_filter('trim', TRUE);

	        // Add some rules, the input field, followed by a list of checks, carried out in order
			///$post->add_rules('username','required','length[3,16]', 'alpha');
			$post->add_rules('resetemail','required','email','length[4,64]');
			
			$post->add_callbacks('resetemail', array($this,'email_exists_chk'));

			if ($post->validate())
	    	{
				$user = ORM::factory('user',$post->resetemail);
				
				// Existing User??
				if ($user->loaded==true)
				{
					//$user->username = $post->username;
					$new_password = $this->_generate_password();
					
					$details_sent = $this->_email_details($post->resetemail,$user->username,$new_password );
					
					if( $details_sent ) {
						$user->email = $post->resetemail;
					
						$user->password = $new_password;
					
						$user->save();
					}
					$form_saved = TRUE;
					$form_action = "EDITED";
				}
					
			}
            else
            {
				// repopulate the form fields
	            $form = arr::overwrite($form, $post->as_array());

	            // populate the error fields, if any
	            $errors = arr::overwrite($errors, $post->errors('auth'));
				$form_error = TRUE;
			}
	    }

        $this->template->form = $form;
	    $this->template->errors = $errors;
		$this->template->form_error = $form_error;
		$this->template->form_saved = $form_saved;
		$this->template->form_action = $form_action;

		// Javascript Header
		//TODO create reset_password js file.
		$this->template->js = new View('admin/reset_password_js');
	}

	/**
	 * Checks if username already exists.
     * @param Validation $post $_POST variable with validation rules
	 */
	public function username_exists_chk(Validation $post)
	{
		$users = ORM::factory('user');
		// If add->rules validation found any errors, get me out of here!
		if (array_key_exists('username', $post->errors()))
			return;

		if( $users->username_exists($post->username) )
			$post->add_error( 'username', 'exists');
	}

	/**
	 * Checks if email address is associated with an account.
	 * @param Validation $post $_POST variable with validation rules
	 */
	public function email_exists_chk( Validation $post )
	{
		$users = ORM::factory('user');
		if( array_key_exists('resetemail',$post->errors()))
			return;

		if( !$users->email_exists( $post->resetemail ) )
			$post->add_error('resetemail','invalid');
	}
	
	/**
	 * Generate random password for the user.
	 * 
	 * @return the new password
	 */
	public function _generate_password()
	{
		$password_chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
		$chars_length = strlen( $password_chars ) - 1;
		$password = NULL;
		
		for( $i = 0; $i < 8; $i++ )
		{
			$position = mt_rand(0,$chars_length);	
			$password .= $password_chars[$position];
		}
		
		return $password;	
	}
	
	/**
	 * Email details to the user.
	 * 
	 * @param the email address of the user requesting a password reset.
	 * @param the username of the user requesting a password reset.
	 * @param the new generated password.
	 * 
	 * @return void.
	 */
	public function _email_details( $email, $username,$password )
	{
		$to = $email;
		$from = 'henry@ushahidi.com';
		$subject = 'Ushahidi password reset.';
		$message = 'Please per your request. See below for your new password.\n\r';
		$message .= "Username: $username\n\r";
		$message .= "Password: $password\n\r";
		
		//email details
		if( email::send( $to, $from, $subject, $message, TRUE ) == 1 )
		{
			return TRUE;
		}
		else 
		{
			return FALSE;
		}
	
	}	
}