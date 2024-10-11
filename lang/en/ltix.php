<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'ltix', language 'en'.
 *
 * @package    core_ltix
 * @author     Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['accept'] = 'Accept';
$string['accepted'] = 'Accepted';
$string['accept_grades_admin'] = 'Accept grades from the tool';
$string['accept_grades_admin_help'] = 'Specify whether the tool provider can add, update, read, and delete grades associated with instances of this tool.

Some tool providers support reporting grades back to Moodle based on actions taken within the tool, creating a more integrated
experience.';
$string['action'] = 'Action';
$string['activate'] = 'Activate';
$string['active'] = 'Active';
$string['add_ltiadv'] = 'Add LTI Advantage';
$string['add_ltilegacy'] = 'Add Legacy LTI';
$string['addtool'] = 'Add tool';
$string['addtype'] = 'Add preconfigured tool';
$string['always'] = 'Always';
$string['autoaddtype'] = 'Add tool';
$string['baseurl'] = 'Base URL/tool registration name';
$string['cancel'] = 'Cancel';
$string['capabilities'] = 'Capabilities';
$string['capabilitiesrequired'] = 'This tool requires access to the following data in order to activate:';
$string['capabilities_help'] = 'Select those capabilities which you wish to offer to the tool provider.  More than one capability can be selected.';
$string['cleanaccesstokens'] = 'External tool removal of expired access tokens';
$string['click_to_continue'] = '<a href="{$a->link}" target="_top">Click to continue</a>';
$string['clientidadmin'] = 'Client ID';
$string['clientidadmin_help'] = 'The client ID is a unique value used to identify a tool. It is created automatically for each tool which uses the JWT security profile introduced in LTI 1.3 and should be part of the details passed to the tool provider so that they can configure the connection at their end.';
$string['configured'] = 'Configured';
$string['confirmtoolactivation'] = 'Are you sure you would like to activate this tool?';
$string['contentitem_deeplinking'] = 'Supports Deep Linking (Content-Item Message)';
$string['contentitem_deeplinking_help'] = 'If ticked, the option \'Select content\' will be available when adding an external tool.';
$string['contentitem_multiple_description'] = 'The following items will be added to your course:';
$string['contentitem_multiple_graded'] = 'Graded activity (Maximum grade: {$a})';
$string['contentselected'] = 'Content selected';
$string['courseactivitiesorresources'] = 'Course activities or resources';
$string['courseexternaltooladd'] = 'Add new LTI External tool';
$string['courseexternaltooladdsuccess'] = '{$a} added.';
$string['courseexternaltooledit'] = 'Edit {$a}';
$string['courseexternaltooleditsuccess'] = 'Changes saved.';
$string['courseexternaltooliconalt'] = 'Icon for {$a}';
$string['courseexternaltools'] = 'LTI External tools';
$string['courseexternaltoolsinfo'] = 'LTI External tools are add-on apps you can integrate into your course, such as interactive content or assessments. Your students can access and use them without leaving your course.';
$string['courseexternaltoolsnoeditpermissions'] = 'You don\'t have permission to edit this tool';
$string['courseexternaltoolsnoviewpermissions'] = 'View course external tools';
$string['courseinformation'] = 'Course information';
$string['courselink'] = 'Go to course';
$string['coursetooldeleted'] = '{$a} deleted';
$string['createdon'] = 'Created on';
$string['custom'] = 'Custom parameters';
$string['custom_help'] = 'Custom parameters are settings used by the tool provider. For example, a custom parameter may be used to display
a specific resource from the provider.  Each parameter should be entered on a separate line using a format of "name=value"; for example, "chapter=3".

It is safe to leave this field unchanged unless directed by the tool provider.';
$string['default_launch_container'] = 'Default launch container';
$string['default_launch_container_help'] = 'The launch container affects the display of the tool when launched from the course. Some launch containers provide more screen
real estate to the tool, and others provide a more integrated feel with the Moodle environment.

* **Default** - Use the launch container specified by the tool configuration.
* **Embed** - The tool is displayed within the existing Moodle window, in a manner similar to most other Activity types.
* **Embed, without blocks** - The tool is displayed within the existing Moodle window, with just the navigation controls
        at the top of the page.
* **New window** - The tool opens in a new window, occupying all the available space.
        Depending on the browser, it will open in a new tab or a popup window.
        It is possible that browsers will prevent the new window from opening.';
$string['delegate'] = 'Delegate to teacher';
$string['delegate_tool'] = 'As specified in Deep Linking definition or Delegate to teacher';
$string['delete'] = 'Delete';
$string['deletecoursetool'] = 'Delete {$a}';
$string['deletecoursetoolconfirm'] = 'This will delete {$a} from the available LTI tools in your course.';
$string['deletecoursetoolwithusageconfirm'] = '{$a} is currently being used in at least one activity in your course. If you delete this tool, the activities that use it will no longer work.<br><br>Are you sure you want to delete {$a}?';
$string['delete_confirmation'] = 'Are you sure you want to delete this preconfigured tool?';
$string['dontshowinactivitychooser'] = 'Don\'t show in activity chooser';
$string['duplicateregurl'] = 'This registration URL is already in use';
$string['dynreg_update_btn_new'] = 'Register as a new external tool';
$string['dynreg_update_btn_update'] = 'Update';
$string['dynreg_update_name'] = 'Tool name';
$string['dynreg_update_notools'] = 'No tools in context.';
$string['dynreg_update_text'] = 'There are existing tools attached to the registration\'s domain. Do you want to update an already installed
external tool or create a new external tool?';
$string['dynreg_update_url'] = 'Base URL';
$string['dynreg_update_version'] = 'LTI version';
$string['dynreg_update_warn_dupdomain'] = 'It is not recommended to have multiple external tools under the same domain.';
$string['embed'] = 'Embed';
$string['embed_no_blocks'] = 'Embed, without blocks';
$string['enterkeyandsecret'] = 'Enter your consumer key and shared secret';
$string['enterkeyandsecret_help'] = 'If you were given a consumer key and/or shared secret, input them here';
$string['entitycourseexternaltools'] = 'LTI External tools';
$string['errorbadurl'] = 'URL is not a valid tool URL or cartridge.';
$string['errorincorrectconsumerkey'] = 'Consumer key is incorrect.';
$string['errorinvaliddata'] = 'Invalid data: {$a}';
$string['errorinvalidresponseformat'] = 'Invalid Content-Item response format.';
$string['errortooltypenotfound'] = 'LTI tool type not found.';
$string['existing_window'] = 'Existing window';
$string['external_tool_types'] = 'Preconfigured tools';
$string['failedtocreatetooltype'] = 'Failed to create new tool. Please check the URL and try again.';
$string['failedtodeletetoolproxy'] = 'Failed to delete tool registration. You may need to visit "Manage external tool registrations" and delete it manually.';
$string['force_ssl'] = 'Force SSL';
$string['force_ssl_help'] = 'Selecting this option forces all launches to this tool provider to use SSL.

In addition, all web service requests from the tool provider will use SSL.

If using this option, confirm that this Moodle site and the tool provider support SSL.';
$string['icon_url'] = 'Icon URL';
$string['icon_url_help'] = 'The icon URL allows the icon that shows up in the course listing for this activity to be modified. Instead of using the default
LTI icon, an icon which conveys the type of activity may be specified.';
$string['initiatelogin'] = 'Initiate login URL';
$string['initiatelogin_help'] = 'The tool URL to which requests for initiating a login are to be sent.  This URL is required before a message can be successfully sent to the tool.';
$string['jwtsecurity'] = 'LTI 1.3';
$string['keytype'] = 'Public key type';
$string['keytype_help'] = 'The authentication method used to validate the tool.';
$string['keytype_keyset'] = 'Keyset URL';
$string['keytype_rsa'] = 'RSA key';
$string['lti_administration'] = 'Edit preconfigured tool';
$string['ltix_launch_error'] = 'An error occurred when launching the external tool:';
$string['ltix_launch_error_tool_request'] = '<p>
To submit a request for an administrator to complete the tool configuration, click <a href="{$a->admin_request_url}" target="_top">here</a>.
</p>';
$string['ltix_launch_error_unsigned_help'] = '<p>This error may be a result of a missing consumer key and shared secret for the tool provider.</p>
<p>If you have a consumer key and shared secret, you may enter it when editing the external tool instance (make sure advanced options are visible).</p>
<p>Alternatively, you may <a href="{$a->course_tool_editor}">create a course level tool provider configuration</a>.</p>';
$string['lti_tool_request_added'] = 'Tool configuration request successfully submitted. You may need to contact an administrator to complete the tool configuration.';
$string['lti_tool_request_existing'] = 'A tool configuration for the tool domain has already been submitted.';
$string['ltiversion'] = 'LTI version';
$string['ltiversion_help'] = 'The version of LTI being used for signing messages and service requests: LTI 1.0/1.1 and LTI 2.0 use the OAuth 1.0A security profile; LTI 1.3.0 uses JWTs.';
$string['ltiunknownserviceapicall'] = 'LTI unknown service API call.';
$string['ltix'] = 'LTI';
$string['main_admin'] = 'General help';
$string['main_admin_help'] = 'External tools allow Moodle users to interact with learning resources hosted remotely. Through a special launch protocol, the remote tool has access to general information about the launching user. For example, the institution name, course ID, user ID, and other information such as the user\'s name or email address.

Tools listed on this page are separated into three categories:

* **Active** - These tool providers have been approved and configured by an administrator. They can be used from within any course on this site. If a consumer key and shared secret are entered, a trust relationship is established between this site and the remote tool, providing a secure communication channel.
* **Pending** - These tool providers came in through a package import, but have not been configured by an administrator. Teachers may still use tools from these providers if they have a consumer key and shared secret, or if none is required.
* **Rejected** - These tools providers are flagged as ones which an administrator has no intention of making available on the site. Teachers may still use tools from these providers if they have a consumer key and shared secret, or if none is required.';
$string['manage_external_tools'] = 'Manage tools';
$string['manage_tools'] = 'Manage preconfigured tools';
$string['manage_tool_proxies'] = 'Manage external tool registrations';
$string['manuallyaddtype'] = 'Alternatively, you can <a href="{$a}">configure a tool manually</a>.';
$string['miscellaneous'] = 'Miscellaneous';
$string['name'] = 'Name';
$string['never'] = 'Never';
$string['new_window'] = 'New window';
$string['no_lti_configured'] = 'There are no active external tools configured.';
$string['no_lti_pending'] = 'There are no pending external tools.';
$string['no_lti_rejected'] = 'There are no rejected external tools.';
$string['no_lti_tools'] = 'There are no external tools configured.';
$string['no_tp_accepted'] = 'There are no accepted external tool registrations.';
$string['no_tp_cancelled'] = 'There are no cancelled external tool registrations.';
$string['no_tp_configured'] = 'There are no unregistered external tool registrations configured.';
$string['no_tp_pending'] = 'There are no pending external tool registrations.';
$string['no_tp_rejected'] = 'There are no rejected external tool registrations.';
$string['nocourseexternaltoolsnotice'] = 'There are no LTI External tools yet.';
$string['noprofileservice'] = 'Profile service not found';
$string['oauthsecurity'] = 'LTI 1.0/1.1';
$string['opensslconfiginvalid'] = 'LTI 1.3 requires a valid openssl.cnf to be configured and available to your web server. Please contact the site administrator to configure and enable openssl for this site.';
$string['organizationid_default'] = 'Default organisation ID';
$string['organizationid_default_help'] = 'The default value to use for Organisation ID. Site ID identifies this installation of Moodle.';
$string['organizationidguid'] = 'Organisation ID';
$string['organizationidguid_help'] = 'A unique identifier for this Moodle instance passed to the tool as the Platform Instance GUID.

If this field is left blank, the default value will be used.';
$string['organizationurl'] = 'Organisation URL';
$string['organizationurl_help'] = 'The base URL of this Moodle instance.

If this field is left blank, a default value will be used based on the site configuration.';
$string['parameter'] = 'Tool parameters';
$string['parameter_help'] = 'Tool parameters are settings requested to be passed by the tool provider in the accepted tool proxy.';
$string['password'] = 'Shared secret';
$string['password_help'] = 'For pre-configured tools, it is not necessary to enter a shared secret here, as the shared secret will be
provided as part of the configuration process.

This field should be entered if creating a link to a tool provider which is not already configured.
If the tool provider is to be used more than once in this course, adding a course tool configuration is a good idea.

The shared secret can be thought of as a password used to authenticate access to the tool. It should be provided
along with the consumer key from the tool provider.

Tools which do not require secure communication from Moodle and do not provide additional services (such as grade reporting)
may not require a shared secret.';
$string['password_admin'] = 'Shared secret';
$string['password_admin_help'] = 'The shared secret can be thought of as a password used to authenticate access to the tool. It should be provided
along with the consumer key from the tool provider.

Tools which do not require secure communication from Moodle and do not provide additional services (such as grade reporting)
may not require a shared secret.';
$string['pending'] = 'Pending';
$string['privacy'] = 'Privacy';
$string['privacy:metadata'] = 'The LTIx subsystem does not store any personal data.';
$string['publickey'] = 'Public key';
$string['publickeyset'] = 'Public keyset';
$string['publickeyset_help'] = 'Public keyset from where this site will retrieve the tool\'s public key to allow signatures of incoming messages and service requests to be verified.';
$string['publickey_help'] = 'The public key (in PEM format) provided by the tool to allow signatures of incoming messages and service requests to be verified.';
$string['redirectionuris'] = 'Redirection URI(s)';
$string['redirectionuris_help'] = 'A list of URIs (one per line) which the tool uses when making authorisation requests.  At least one must be registered before a message can be successfully sent to the tool.';
$string['register'] = 'Register';
$string['register_warning'] = 'The registration page seems to be taking a while to open. If it does not appear, check that you entered the correct URL in the configuration settings. If Moodle is using https, ensure the tool you are configuring supports https and you are using https in the URL.';
$string['registertype'] = 'Configure a new external tool registration';
$string['registration_options'] = 'Registration options';
$string['registrationname'] = 'Tool provider name';
$string['registrationname_help'] = 'Enter the name of the tool provider being registered.';
$string['registrationurl'] = 'Registration URL';
$string['registrationurl_help'] = 'The registration URL should be available from the tool provider as the location to which registration requests should be sent.';
$string['reject'] = 'Reject';
$string['rejected'] = 'Rejected';
$string['resourcekey'] = 'Consumer key';
$string['resourcekey_help'] = 'For pre-configured tools, it is not necessary to enter a resource key here, as the consumer key will be
provided as part of the configuration process.

This field should be entered if creating a link to a tool provider which is not already configured.
If the tool provider is to be used more than once in this course, adding a course tool configuration is a good idea.

The consumer key can be thought of as a username used to authenticate access to the tool.
It can be used by the tool provider to uniquely identify the Moodle site from which users launch into the tool.

The consumer key must be provided by the tool provider. The method of obtaining a consumer key varies between
tool providers. It may be an automated process, or it may require a dialogue with the tool provider.

Tools which do not require secure communication from Moodle and do not provide additional services (such as grade reporting)
may not require a resource key.';
$string['resourcekey_admin'] = 'Consumer key';
$string['resourcekey_admin_help'] = 'The consumer key can be thought of as a username used to authenticate access to the tool.
It can be used by the tool provider to uniquely identify the Moodle site from which users launch into the tool.

The consumer key must be provided by the tool provider. The method of obtaining a consumer key varies between
tool providers. It may be an automated process, or it may require a dialogue with the tool provider.

Tools which do not require secure communication from Moodle and do not provide additional services (such as grade reporting)
may not require a resource key.';
$string['restricttocategory'] = 'Restrict to category';
$string['restricttocategory_help'] = 'To restrict use of this tool to courses within a category, select the category or categories from the list.';
$string['return_to_course'] = 'Click <a href="{$a->link}" target="_top">here</a> to return to the course.';
$string['secure_icon_url'] = 'Secure icon URL';
$string['secure_icon_url_help'] = 'Similar to the icon URL, but used when the site is accessed securely through SSL. This field is to prevent the browser from displaying a warning about an insecure image.';
$string['services'] = 'Services';
$string['services_help'] = 'Select those services which you wish to offer to the tool provider.  More than one service can be selected.';
$string['share_email_admin'] = 'Share launcher\'s email with tool';
$string['share_email_admin_help'] = 'Specify whether the e-mail address of the user launching the tool will be shared with the tool provider.
The tool provider may need launcher\'s e-mail addresses to distinguish users with the same name in the UI, or send e-mails
to users based on actions within the tool.';
$string['share_name_admin'] = 'Share launcher\'s name with tool';
$string['share_name_admin_help'] = 'Specify whether the full name of the user launching the tool should be shared with the tool provider.
The tool provider may need launchers\' names to show meaningful information within the tool.';
$string['showinactivitychooser'] = 'Show in activity chooser';
$string['show_in_course_activity_chooser'] = 'Show in activity chooser and as a preconfigured tool';
$string['show_in_course_lti1'] = 'Tool configuration usage';
$string['show_in_course_lti1_help'] = 'This tool may be shown in the activity chooser for a teacher to select to add to a course. Alternatively, it may be shown in the preconfigured tool drop-down menu when adding an external tool to a course. A further option is for the tool configuration to only be used if the exact tool URL is entered when adding an external tool to a course.';
$string['show_in_course_lti2'] = 'Tool configuration usage';
$string['show_in_course_lti2_help'] = 'This tool can be shown in the activity chooser for a teacher to select to add to a course or in the preconfigured tool drop-down menu when adding an external tool to a course.';
$string['show_in_course_no'] = 'Do not show; use only when a matching tool URL is entered';
$string['show_in_course_preconfigured'] = 'Show as preconfigured tool when adding an external tool';
$string['siteid'] = 'Site ID';
$string['sitehost'] = 'Site hostname';
$string['successfullycreatedtooltype'] = 'Successfully created new tool!';
$string['successfullyfetchedtoolconfigurationfromcontent'] = 'Successfully fetched tool configuration from the selected content.';
$string['tool_settings'] = 'Tool settings';
$string['tooldescription'] = 'Tool description';
$string['tooldescription_help'] = 'The description of the tool that will be displayed to teachers in the activity list.

This should describe what the tool is for and what it does and any additional information the teacher may need to know.';
$string['tooldetailsaccesstokenurl'] = 'Access token URL';
$string['tooldetailsauthrequesturl'] = 'Authentication request URL';
$string['tooldetailsclientid'] = 'Client ID';
$string['tooldetailsdeploymentid'] = 'Deployment ID';
$string['tooldetailsmailtosubject'] = 'LTI tool configuration';
$string['tooldetailsmodalemail'] = 'Email';
$string['tooldetailsmodallink'] = 'View configuration details';
$string['tooldetailsmodaltitle'] = 'Tool configuration details';
$string['tooldetailsplatformid'] = 'Platform ID';
$string['tooldetailspublickeyseturl'] = 'Public keyset URL';
$string['toolisbeingused'] = 'This tool is being used {$a} times';
$string['toolisnotbeingused'] = 'This tool has not yet been used';
$string['toolproxy'] = 'External tool registrations';
$string['toolproxy_help'] = 'External tool registrations allow Moodle site administrators to configure external tools from a tool proxy obtained from a tool provider supporting LTI 2.0. A registration URL provided by the tool provider is all that is required to initiate the process. The capabilities and services offered to the tool provider are selected when configuring a new registration.

Tool registrations listed on this page are separated into four categories:

* **Configured** - These tool registrations have been set up but the registration process has not yet been started.
* **Pending** - The registration process for these tool registrations has been started but has not completed. Open and save the settings to move it
back to the \'Configured\' category.
* **Accepted** - These tool registrations have been approved; the resources specified in the tool proxy will appear on the preconfigured tools page
with an initial status of \'Pending\'.
* **Rejected** - These tool registrations are ones which were rejected during the registration process. Open and save the settings to move it
back to the \'Configured\' category so the registration process can be restarted.';
$string['toolproxyregistration'] = 'External tool registration';
$string['toolregistration'] = 'External tool registration';
$string['toolsetup'] = 'External tool configuration';
$string['toolurl'] = 'Tool URL';
$string['toolurl_help'] = 'The tool URL is used to match tool URLs to the correct tool configuration. Prefixing the URL with http(s) is optional.

Additionally, the base URL is used as the tool URL if a tool URL is not specified in the external tool instance.

For example, a base URL of *tool.com* would match the following:

* tool.com
* tool.com/quizzes
* tool.com/quizzes/quiz.php?id=10
* www.tool.com/quizzes

A base URL of *www.tool.com/quizzes* would match the following:

* www.tool.com/quizzes
* tool.com/quizzes
* tool.com/quizzes/take.php?id=10

A base URL of *quiz.tool.com* would match the following:

* quiz.tool.com
* quiz.tool.com/take.php?id=10

If two different tool configurations are for the same domain, the most specific match will be used.

You can also insert a cartridge URL if you have one and the details for the tool will be automatically filled.';
$string['toolurl_contentitemselectionrequest'] = 'Content Selection URL';
$string['toolurl_contentitemselectionrequest_help'] = 'The Content Selection URL will be used to launch the content selection page from the tool provider. If it is empty, the Tool URL will be used';
$string['toolurlplaceholder'] = 'Tool URL...';
$string['tooltypes'] = 'Tools';
$string['typename'] = 'Tool name';
$string['typename_help'] = 'The tool name is used to identify the tool provider within Moodle. The name entered will be visible to teachers when adding external tools within courses.';
$string['unabletocreatetooltype'] = 'Unable to create tool';
$string['unabletofindtooltype'] = 'Unable to find tool for {$a->id}';
$string['unknownstate'] = 'Unknown state';
$string['update'] = 'Update';
$string['usage'] = 'Usage count';
$string['useraccountinformation'] = 'User account information';
$string['userpersonalinformation'] = 'User personal information';

