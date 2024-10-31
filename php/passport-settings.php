<div class='wrap passport-settings'>
  <h2>Passport Settings</h2>
  <div id="passport-settings-header"></div>
  <div id="passport-settings-body">

    <div>
      <form method='post' action='options.php'>
        <?php settings_fields('passport_settings'); ?>
        <?php do_settings_sections('passport_settings'); ?>
        <div id="passport-settings-section-general-settings" class="passport-settings-section">
          <div class='form-padding'>
            <table class='form-table'>
              <tr valign='top'>
                <th scope='row'>Login redirects to:</th>
                <td>
                  <select name='passport_login_redirect'>
                    <option
                      value='home_page' <?php selected(get_option('passport_login_redirect'), 'home_page'); ?>>
                      Home Page
                    </option>
                    <option
                      value='last_page' <?php selected(get_option('passport_login_redirect'), 'last_page'); ?>>
                      Last Page
                    </option>
                    <option
                      value='admin_dashboard' <?php selected(get_option('passport_login_redirect'), 'admin_dashboard'); ?>>
                      Admin Dashboard
                    </option>
                    <option
                      value='user_profile' <?php selected(get_option('passport_login_redirect'), 'user_profile'); ?>>
                      User's Profile Page
                    </option>
                  </select>
                  <?php wp_dropdown_pages(array("id" => "passport_login_redirect_page", "name" => "passport_login_redirect_page", "selected" => get_option('passport_login_redirect_page'))); ?>
                  <input type="text" name="passport_login_redirect_url"
                         value="<?php echo get_option('passport_login_redirect_url'); ?>"
                         style="display:none;"/>
                </td>
              </tr>

              <tr valign='top'>
                <th scope='row'>Logout redirects to:</th>
                <td>
                  <select name='passport_logout_redirect'>
                    <option
                      value='home_page' <?php selected(get_option('passport_logout_redirect'), 'home_page'); ?>>
                      Home Page
                    </option>
                    <option
                      value='last_page' <?php selected(get_option('passport_logout_redirect'), 'last_page'); ?>>
                      Last Page
                    </option>
                    <option
                      value='admin_dashboard' <?php selected(get_option('passport_logout_redirect'), 'admin_dashboard'); ?>>
                      Admin Dashboard
                    </option>
                    <option
                      value='user_profile' <?php selected(get_option('passport_logout_redirect'), 'user_profile'); ?>>
                      User's Profile Page
                    </option>
                  </select>
                  <?php wp_dropdown_pages(array("id" => "passport_logout_redirect_page", "name" => "passport_logout_redirect_page", "selected" => get_option('passport_logout_redirect_page'))); ?>
                  <input type="text" name="passport_logout_redirect_url"
                         value="<?php echo get_option('passport_logout_redirect_url'); ?>"
                         style="display:none;"/>
                </td>
              </tr>

              <tr valign='top'>
                <th scope='row'>Automatically logout inactive users:</th>
                <td>
                  <select name='passport_logout_inactive_users'>
                    <option
                      value='0' <?php selected(get_option('passport_logout_inactive_users'), '0'); ?>>
                      Never
                    </option>
                    <option
                      value='1' <?php selected(get_option('passport_logout_inactive_users'), '1'); ?>>
                      After 1 minute
                    </option>
                    <option
                      value='5' <?php selected(get_option('passport_logout_inactive_users'), '5'); ?>>
                      After 5 minutes
                    </option>
                    <option
                      value='15' <?php selected(get_option('passport_logout_inactive_users'), '15'); ?>>
                      After 15 minutes
                    </option>
                    <option
                      value='30' <?php selected(get_option('passport_logout_inactive_users'), '30'); ?>>
                      After 30 minutes
                    </option>
                    <option
                      value='60' <?php selected(get_option('passport_logout_inactive_users'), '60'); ?>>
                      After 1 hour
                    </option>
                    <option
                      value='120' <?php selected(get_option('passport_logout_inactive_users'), '120'); ?>>
                      After 2 hours
                    </option>
                    <option
                      value='240' <?php selected(get_option('passport_logout_inactive_users'), '240'); ?>>
                      After 4 hours
                    </option>
                  </select>
                </td>
              </tr>

              <tr valign='top'>
                <th scope='row'>Suppress default welcome email:
                </th>
                <td>
                  <input type='checkbox' name='passport_suppress_welcome_email'
                         value='1' <?php checked(get_option('passport_suppress_welcome_email') == 1); ?> />
                </td>
              </tr>


              <th scope='row'>Passport Backend URI:</th>
              <td>
                <input type='text' name='passport_backend_url'
                       value='<?php echo get_option('passport_backend_url'); ?>'/>
              </td>
              </tr>

              <th scope='row'>Passport Frontend URI:</th>
              <td>
                <input type='text' name='passport_frontend_url'
                       value='<?php echo get_option('passport_frontend_url'); ?>'/>
              </td>
              </tr>

              <tr valign='top'>
                <th scope='row'>Client ID:</th>
                <td>
                  <input type='text' name='passport_api_id'
                         value='<?php echo get_option('passport_api_id'); ?>'/>
                </td>
              </tr>

              <tr valign='top'>
                <th scope='row'>Client Secret:</th>
                <td>
                  <input type='text' name='passport_api_secret'
                         value='<?php echo get_option('passport_api_secret'); ?>'/>
                </td>
              </tr>

              <tr valign='top'>
                <th scope='row'>API Key:</th>
                <td>
                  <input type='text' name='passport_api_key'
                         value='<?php echo get_option('passport_api_key'); ?>'/>
                </td>
              </tr>

            </table>
            <p>
              <strong>Instructions:</strong>
            <ol>
              <li>Enter the URL for Passport into the field Passport URI</li>
              <li>Login to Passport Backend</li>
              <li>Create an Application called Wordpress in Passport and enter the the wordpress sites URl into
                Authorized redirect URLs
              </li>
              <li>Copy the Client ID from Passport into the field Client ID in Wordpress</li>
              <li>Copy the Client Secret from Passport into the field Client Secret in Wordpress</li>
              <li>In Passport click Manage Roles next the wordpress application and add the default wordpress roles</li>
              <li>If Passport Frontend does not have an API Key, create one</li>
              <li>Create an API Key for wordpress and copy it into the field API Key</li>
            </ol>
            <ol>
            </ol>
            </p>
            <?php submit_button('Save all settings'); ?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>