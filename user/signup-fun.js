function user_signup_check_email(id) {
	elem(id+'_email_chk').innerHTML = request(the_url+'/user/signup-email',formdata(id)).responseText;
}

function user_signup_check_username(id) {
	elem(id+'_username_chk').innerHTML = request(the_url+'/user/signup-username',formdata(id)).responseText;
}
