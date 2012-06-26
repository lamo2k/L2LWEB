var c_cache        = new Array();

function RunAjaxJS(insertelement, data){
	var milisec = new Date;
    var jsfound = false;
    milisec = milisec.getTime();

    var js_reg = /<script.*?>(.|[\r\n])*?<\/script>/ig;

    var js_str = js_reg.exec(data);
    if (js_str != null) {

		var js_arr = new Array(js_str.shift());
        var jsfound = true;
        
        while(js_str) {
           js_str = js_reg.exec(data);
           if (js_str != null) js_arr.push(js_str.shift());
        }

        for(var i=0; i<js_arr.length;i++) {
           data = data.replace(js_arr[i],'<span id="'+milisec+i+'" style="display:none;"></span>');
        }
	}
    
	$("#" + insertelement).html(data);

    if (jsfound) {

       var js_content_reg = /<script.*?>((.|[\r\n])*?)<\/script>/ig;

       for (i = 0; i < js_arr.length; i++) {
           var mark_node = document.getElementById(milisec+''+i);
           var mark_parent_node = mark_node.parentNode;
           mark_parent_node.removeChild(mark_node);
                                
           js_content_reg.lastIndex = 0;
           var js_content = js_content_reg.exec(js_arr[i]);
           var script_node = mark_parent_node.appendChild(document.createElement('script'));
		   script_node.text = js_content[1];  

           var script_params_str = js_arr[i].substring(js_arr[i].indexOf(' ',0),js_arr[i].indexOf('>',0));
           var params_arr = script_params_str.split(' ');

           if (params_arr.length > 1) {
              for (var j=0;j< params_arr.length; j++ ) {
                                        
                  if(params_arr[j].length > 0){
                       var param_arr = params_arr[j].split('=');
                       param_arr[1] = param_arr[1].substr(1,(param_arr[1].length-2));
                       script_node.setAttribute(param_arr[0],param_arr[1]);
                  }

               }
           }

       }
    }
};

function IPMenu( m_ip, l1, l2, l3 ){

	var menu=new Array();
	
	menu[0]='<a href="https://www.nic.ru/whois/?ip=' + m_ip + '" target="_blank">' + l1 + '</a>';
	menu[1]='<a href="' + dle_root + dle_admin + '?mod=iptools&ip=' + m_ip + '" target="_blank">' + l2 + '</a>';
	menu[2]='<a href="' + dle_root + dle_admin + '?mod=blockip&ip=' + m_ip + '" target="_blank">' + l3 + '</a>';
	
	return menu;
};


function ajax_save_for_edit( news_id, event )
{
	var allow_br = 0;
	var news_txt = '';

	var params = {};

	if (quick_wysiwyg == "1") {

		submit_all_data();

	}

	$.each($('#ajaxnews'+news_id).serializeArray(), function(index,value) {

		if (value.name.indexOf("xfield") != -1) {
			params[value.name] = value.value;
		}

	});

	if (document.getElementById('allow_br_'+news_id).checked) { params['allow_br'] = 1; }

	params['news_txt'] = $('#dleeditnews'+news_id).val();
	params['full_txt'] = $('#dleeditfullnews'+news_id).val();
	params['title'] = $('#edit-title-'+news_id).val();
	params['reason'] = $('#edit-reason-'+news_id).val();
	params['id'] = news_id;
	params['field'] = event;
	params['action'] = "save";

	ShowLoading('');

	$.post(dle_root + "engine/ajax/editnews.php", params, function(data){

		HideLoading('');

		if (data != "ok") {

			DLEalert ( data, dle_info );

		} else {

			$('#dlepopup-news-edit').dialog('close');
		    DLEconfirm( dle_save_ok, dle_confirm, function () {
				location.reload(true);
			} );

		}

	});

	return false;
};

function ajax_prep_for_edit( news_id, event )
{
	for (var i = 0, length = c_cache.length; i < length; i++) {
	    if (i in c_cache) {
			if ( c_cache[ i ] || c_cache[ i ] != '' )
			{
				ajax_cancel_comm_edit( i );
			}
	    }
	}

	ShowLoading('');

	$.get(dle_root + "engine/ajax/editnews.php", { id: news_id, field: event, action: "edit" }, function(data){

		HideLoading('');
		var shadow = 'none';

		$('#modal-overlay').remove();

		$('body').prepend('<div id="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #666666; opacity: .40;filter:Alpha(Opacity=40); z-index: 999; display:none;"></div>');
		$('#modal-overlay').css({'filter' : 'alpha(opacity=40)'}).fadeIn();

		var b = {};
	
		b[dle_act_lang[3]] = function() { 
			$(this).dialog('close');	
		};
	
		b[dle_act_lang[4]] = function() { 
			ajax_save_for_edit( news_id, event );			
		};
	
		$('#dlepopup-news-edit').remove();
						
		$('body').prepend("<div id='dlepopup-news-edit' class='dlepopupnewsedit' title='"+menu_short+"' style='display:none'></div>");

		$('.dlepopupnewsedit').html('');

		$('#dlepopup-news-edit').dialog({
			autoOpen: true,
			width: "800",
			height: 500,
			buttons: b,
			dialogClass: "modalfixed",
			dragStart: function(event, ui) {
				shadow = $(".modalfixed").css('box-shadow');
				$(".modalfixed").css('box-shadow', 'none');
			},
			dragStop: function(event, ui) {
				$(".modalfixed").css('box-shadow', shadow);
			},
			close: function(event, ui) {
					$(this).dialog('destroy');
					$('#modal-overlay').fadeOut(function() {
			        $('#modal-overlay').remove();
			    });
			 }
		});

		if ($(window).width() > 830 && $(window).height() > 530 ) {
			$('.modalfixed.ui-dialog').css({position:"fixed"});
			$( '#dlepopup-news-edit').dialog( "option", "position", ['0','0'] );
		}

		RunAjaxJS('dlepopup-news-edit', data);

	});

	return false;
};


function ajax_comm_edit( c_id, area )
{

	for (var i = 0, length = c_cache.length; i < length; i++) {
	    if (i in c_cache) {
			if ( c_cache[ i ] != '' )
			{
				ajax_cancel_comm_edit( i );
			}
	    }
	}

	if ( ! c_cache[ c_id ] || c_cache[ c_id ] == '' )
	{
		c_cache[ c_id ] = $('#comm-id-'+c_id).html();
	}

	ShowLoading('');

	$.get(dle_root + "engine/ajax/editcomments.php", { id: c_id, area: area, action: "edit" }, function(data){

		HideLoading('');

		RunAjaxJS('comm-id-'+c_id, data);

		setTimeout(function() {
           $("html:not(:animated)"+( ! $.browser.opera ? ",body:not(:animated)" : "")).animate({scrollTop: $("#comm-id-" + c_id).offset().top - 70}, 700);
        }, 100);

	});
	return false;
};

function ajax_cancel_comm_edit( c_id )
{
	if ( c_cache[ c_id ] != "" )
	{
		$("#comm-id-"+c_id).html(c_cache[ c_id ]);
	}

	c_cache[ c_id ] = '';

	return false;
};

function ajax_save_comm_edit( c_id, area )
{
	if (dle_wysiwyg == "yes") {

		submit_all_data();

	}

	var comm_txt = $('#dleeditcomments'+c_id).val();

	ShowLoading('');

	$.post(dle_root + "engine/ajax/editcomments.php", { id: c_id, comm_txt: comm_txt, area: area, action: "save" }, function(data){

		HideLoading('');
		c_cache[ c_id ] = '';
		$("#comm-id-"+c_id).html(data);

	});
	return false;
};

function DeleteComments(id, hash) {

    DLEconfirm( dle_del_agree, dle_confirm, function () {

		ShowLoading('');
	
		$.get(dle_root + "engine/ajax/deletecomments.php", { id: id, dle_allow_hash: hash }, function(r){
	
			HideLoading('');
	
			r = parseInt(r);
		
			if (!isNaN(r)) {
		
				$("html"+( ! $.browser.opera ? ",body" : "")).animate({scrollTop: $("#comment-id-" + r).offset().top - 70}, 700);
		
				setTimeout(function() { $("#comment-id-" + r).hide('blind',{},1400)}, 700);
				
			}
	
		});

	} );

};

function doFavorites( fav_id, event )
{
	ShowLoading('');

	$.get(dle_root + "engine/ajax/favorites.php", { fav_id: fav_id, action: event, skin: dle_skin }, function(data){

		HideLoading('');

		$("#fav-id-" + fav_id).html(data);

	});

	return false;
};

function CheckLogin()
{
	var name = document.getElementById('name').value;

	ShowLoading('');

	$.post(dle_root + "engine/ajax/registration.php", { name: name }, function(data){

		HideLoading('');

		$("#result-registration").html(data);

	});

	return false;
};

function doCalendar(month, year, effect){

	ShowLoading('');

	$.get(dle_root + "engine/ajax/calendar.php", { month: month, year: year }, function(data){
		HideLoading('');

		if (effect == "left" ) {
			$("#calendar-layer").hide('slide',{ direction: "left" }, 500).html(data).show('slide',{ direction: "right" }, 500);
		} else {
			$("#calendar-layer").hide('slide',{ direction: "right" }, 500).html(data).show('slide',{ direction: "left" }, 500);
		}

	});
};


function doRate( rate, id ) {
	ShowLoading('');

	$.get(dle_root + "engine/ajax/rating.php", { go_rate: rate, news_id: id, skin: dle_skin }, function(data){

		HideLoading('');

		$("#ratig-layer").html(data);

	});
};

function dleRate( rate, id ) {
	ShowLoading('');

	$.get(dle_root + "engine/ajax/rating.php", { go_rate: rate, news_id: id, skin: dle_skin, mode: "short" }, function(data){

		HideLoading('');

		$("#ratig-layer-" + id).html(data);

	});

};

function doAddComments(){

	var form = document.getElementById('dle-comments-form');

	if (dle_wysiwyg == "yes") {
		submit_all_data();
		var editor_mode = 'wysiwyg';
	} else { var editor_mode = ''; }

	if (form.comments.value == '' || form.name.value == '')
	{
		DLEalert ( dle_req_field, dle_info );
		return false;
	}

	if ( form.question_answer ) {

	   var question_answer = form.question_answer.value;

    } else { var question_answer = ''; }

	if ( form.sec_code ) {

	   var sec_code = form.sec_code.value;

    } else { var sec_code = ''; }

	if ( form.recaptcha_response_field ) {
	   var recaptcha_response_field= Recaptcha.get_response();
	   var recaptcha_challenge_field= Recaptcha.get_challenge();
    } else {
	   var recaptcha_response_field= '';
	   var recaptcha_challenge_field= '';
	}

	if ( form.allow_subscribe ) {

		if ( form.allow_subscribe.checked == true ) {
	
		   var allow_subscribe= "1";

		} else {

		   var allow_subscribe= "0";

		}

    } else { var allow_subscribe= "0"; }

	ShowLoading('');

	$.post(dle_root + "engine/ajax/addcomments.php", { post_id: form.post_id.value, comments: form.comments.value, name: form.name.value, mail: form.mail.value, editor_mode: editor_mode, skin: dle_skin, sec_code: sec_code, question_answer: question_answer, recaptcha_response_field: recaptcha_response_field, recaptcha_challenge_field: recaptcha_challenge_field, allow_subscribe: allow_subscribe }, function(data){

		if ( form.sec_code ) {
           form.sec_code.value = '';
           reload();
	    }

		HideLoading('');

		RunAjaxJS('dle-ajax-comments', data);

		if (data != 'error' && document.getElementById('blind-animation')) {

			$("html"+( ! $.browser.opera ? ",body" : "")).animate({scrollTop: $("#dle-ajax-comments").offset().top - 70}, 1100);
	
			setTimeout(function() { $('#blind-animation').show('blind',{},1500)}, 1100);
		}

	});

};

function CommentsPage( cstart, news_id ) 
{
	ShowLoading('');

	$.get(dle_root + "engine/ajax/comments.php", { cstart: cstart, news_id: news_id, skin: dle_skin }, function(data){

		HideLoading('');

		if (!isNaN(cstart) && !isNaN(news_id)) {

			$('#dle-comm-link').unbind('click');

			$('#dle-comm-link').bind('click', function() {
				CommentsPage( cstart, news_id );
				return false;
			});

		
		}

		scroll( 0, $("#dle-comments-list").offset().top - 70 );
	
		$("#dle-comments-list").html(data.comments); 
		$(".dle-comments-navigation").html(data.navigation); 


	}, "json");

	return false;
};

function dle_copy_quote(qname) 
{
	dle_txt= '';

	if (window.getSelection) 
	{
		dle_txt=window.getSelection();
	}
	else if (document.selection) 
	{
		dle_txt=document.selection.createRange().text;
	}
	if (dle_txt != "")
	{
		dle_txt='[quote='+qname+']'+dle_txt+'[/quote]\n';
	}
};

function dle_ins(name) 
{
	if ( !document.getElementById('dle-comments-form') ) return false;

	var input=document.getElementById('dle-comments-form').comments;
	var finalhtml = "";

	if (dle_wysiwyg == "no") {
		if (dle_txt!= "") {
			input.value += dle_txt;
		}
		else { 
			input.value += "[b]"+name+"[/b],"+"\n";
		}
	} else {
		if (dle_txt!= "") {
			finalhtml = dle_txt;
		}
		else { 
			finalhtml = "<b>"+name+"</b>,";
		}

		oUtil.obj.focus();	
		oUtil.obj.insertHTML(finalhtml+"<br />"); 
	}

};

function ShowOrHide( id ) {

	  var item = $("#" + id);

	  if ( document.getElementById('image-'+ id) ) {

		var image = document.getElementById('image-'+ id);

	  } else {

		var image = null;
	  }

	var scrolltime = (item.height() / 200) * 1000;

	if (scrolltime > 3000 ) { scrolltime = 3000; }

	if (scrolltime < 250 ) { scrolltime = 250; }

	if (item.css("display") == "none") { 

		item.show('blind',{}, scrolltime );

		if (image) { image.src = dle_root + 'templates/'+ dle_skin + '/dleimages/spoiler-minus.gif';}

	} else {

		if (scrolltime > 2000 ) { scrolltime = 2000; }

		item.hide('blind',{}, scrolltime );
		if (image) { image.src = dle_root + 'templates/'+ dle_skin + '/dleimages/spoiler-plus.gif';}
	}

};


function ckeck_uncheck_all() {
    var frm = document.pmlist;
    for (var i=0;i<frm.elements.length;i++) {
        var elmnt = frm.elements[i];
        if (elmnt.type=='checkbox') {
            if(frm.master_box.checked == true){ elmnt.checked=false; }
            else{ elmnt.checked=true; }
        }
    }
    if(frm.master_box.checked == true){ frm.master_box.checked = false; }
    else{ frm.master_box.checked = true; }
};

function confirmDelete(url){

    DLEconfirm( dle_del_agree, dle_confirm, function () {
		document.location=url;
	} );
};

function setNewField(which, formname)
{
	if (which != selField)
	{
		fombj    = formname;
		selField = which;

	}
};

function dle_news_delete( id ){

		var b = {};
	
		b[dle_act_lang[1]] = function() { 
			$(this).dialog("close");						
		};

		if (allow_dle_delete_news) {

			b[dle_del_msg] = function() { 
				$(this).dialog("close");
	
				var bt = {};
						
				bt[dle_act_lang[3]] = function() { 
					$(this).dialog('close');						
				};
						
				bt[dle_p_send] = function() { 
					if ( $('#dle-promt-text').val().length < 1) {
						$('#dle-promt-text').addClass('ui-state-error');
					} else {
						var response = $('#dle-promt-text').val()
						$(this).dialog('close');
						$('#dlepopup').remove();
						$.post(dle_root + 'engine/ajax/message.php', { id: id,  text: response },
							function(data){
								if (data == 'ok') { document.location=dle_root + 'index.php?do=deletenews&id=' + id + '&hash=' + dle_login_hash; } else { DLEalert('Send Error', dle_info); }
						});
		
					}				
				};
						
				$('#dlepopup').remove();
						
				$('body').append("<div id='dlepopup' title='"+dle_notice+"' style='display:none'><br />"+dle_p_text+"<br /><br /><textarea name='dle-promt-text' id='dle-promt-text' class='ui-widget-content ui-corner-all' style='width:97%;height:100px; padding: .4em;'></textarea></div>");
						
				$('#dlepopup').dialog({
					autoOpen: true,
					width: 500,
					dialogClass: "modalfixed",
					buttons: bt
				});

				$('.modalfixed.ui-dialog').css({position:"fixed"});
				$('#dlepopup').dialog( "option", "position", ['0','0'] );
						
			};
		}
	
		b[dle_act_lang[0]] = function() { 
			$(this).dialog("close");
			document.location=dle_root + 'index.php?do=deletenews&id=' + id + '&hash=' + dle_login_hash;					
		};
	
		$("#dlepopup").remove();
	
		$("body").append("<div id='dlepopup' title='"+dle_confirm+"' style='display:none'><br /><div id='dlepopupmessage'>"+dle_del_agree+"</div></div>");
	
		$('#dlepopup').dialog({
			autoOpen: true,
			width: 500,
			dialogClass: "modalfixed",
			buttons: b
		});

		$('.modalfixed.ui-dialog').css({position:"fixed"});
		$('#dlepopup').dialog( "option", "position", ['0','0'] );


};

function MenuNewsBuild( m_id, event ){

var menu=new Array();

menu[0]='<a onclick="ajax_prep_for_edit(\'' + m_id + '\', \'' + event + '\'); return false;" href="#">' + menu_short + '</a>';

if (dle_admin != '') {

	menu[1]='<a href="' + dle_root + dle_admin + '?mod=editnews&action=editnews&id=' + m_id + '" target="_blank">' + menu_full + '</a>';

}

if (allow_dle_delete_news) {

	menu[2]='<a onclick="sendNotice (\'' + m_id + '\'); return false;" href="#">' + dle_notice + '</a>';
	menu[3]='<a onclick="dle_news_delete (\'' + m_id + '\'); return false;" href="#">' + dle_del_news + '</a>';

}

return menu;
};

function sendNotice( id ){
	var b = {};

	b[dle_act_lang[3]] = function() { 
		$(this).dialog('close');						
	};

	b[dle_p_send] = function() { 
		if ( $('#dle-promt-text').val().length < 1) {
			$('#dle-promt-text').addClass('ui-state-error');
		} else {
			var response = $('#dle-promt-text').val()
			$(this).dialog('close');
			$('#dlepopup').remove();
			$.post(dle_root + 'engine/ajax/message.php', { id: id,  text: response, allowdelete: "no" },
				function(data){
					if (data == 'ok') { DLEalert(dle_p_send_ok, dle_info); }
				});

		}				
	};

	$('#dlepopup').remove();
					
	$('body').append("<div id='dlepopup' title='"+dle_notice+"' style='display:none'><br />"+dle_p_text+"<br /><br /><textarea name='dle-promt-text' id='dle-promt-text' class='ui-widget-content ui-corner-all' style='width:97%;height:100px; padding: .4em;'></textarea></div>");
					
	$('#dlepopup').dialog({
		autoOpen: true,
		width: 500,
		dialogClass: "modalfixed",
		buttons: b
	});

	$('.modalfixed.ui-dialog').css({position:"fixed"});
	$('#dlepopup').dialog( "option", "position", ['0','0'] );

};

function AddComplaint( id, action ){
	var b = {};

	b[dle_act_lang[3]] = function() { 
		$(this).dialog('close');						
	};

	b[dle_p_send] = function() { 
		if ( $('#dle-promt-text').val().length < 1) {
			$('#dle-promt-text').addClass('ui-state-error');
		} else {
			var response = $('#dle-promt-text').val()
			$(this).dialog('close');
			$('#dlepopup').remove();
			$.post(dle_root + 'engine/ajax/complaint.php', { id: id,  text: response, action: action },
				function(data){
					if (data == 'ok') { DLEalert(dle_p_send_ok, dle_info); } else { DLEalert(data, dle_info); }
				});

		}				
	};

	$('#dlepopup').remove();
					
	$('body').append("<div id='dlepopup' title='"+dle_complaint+"' style='display:none'><br /><textarea name='dle-promt-text' id='dle-promt-text' class='ui-widget-content ui-corner-all' style='width:97%;height:100px; padding: .4em;'></textarea></div>");
					
	$('#dlepopup').dialog({
		autoOpen: true,
		width: 500,
		dialogClass: "modalfixed",
		buttons: b
	});

	$('.modalfixed.ui-dialog').css({position:"fixed"});
	$('#dlepopup').dialog( "option", "position", ['0','0'] );

};

function DLEalert(message, title){

	$("#dlepopup").remove();

	$("body").append("<div id='dlepopup' title='" + title + "' style='display:none'><br />"+ message +"</div>");

	$('#dlepopup').dialog({
		autoOpen: true,
		width: 470,
		dialogClass: "modalfixed",
		buttons: {
			"Ok": function() { 
				$(this).dialog("close");
				$("#dlepopup").remove();							
			} 
		}
	});

	$('.modalfixed.ui-dialog').css({position:"fixed"});
	$('#dlepopup').dialog( "option", "position", ['0','0'] );
};

function DLEconfirm(message, title, callback){

	var b = {};

	b[dle_act_lang[1]] = function() { 
					$(this).dialog("close");
					$("#dlepopup").remove();						
			    };

	b[dle_act_lang[0]] = function() { 
					$(this).dialog("close");
					$("#dlepopup").remove();
					if( callback ) callback();					
				};

	$("#dlepopup").remove();

	$("body").append("<div id='dlepopup' title='" + title + "' style='display:none'><br />"+ message +"</div>");

	$('#dlepopup').dialog({
		autoOpen: true,
		width: 500,
		dialogClass: "modalfixed",
		buttons: b
	});

	$('.modalfixed.ui-dialog').css({position:"fixed"});
	$('#dlepopup').dialog( "option", "position", ['0','0'] );
};

function DLEprompt(message, d, title, callback, allowempty){

	var b = {};

	b[dle_act_lang[3]] = function() { 
					$(this).dialog("close");						
			    };

	b[dle_act_lang[2]] = function() { 
					if ( !allowempty && $("#dle-promt-text").val().length < 1) {
						 $("#dle-promt-text").addClass('ui-state-error');
					} else {
						var response = $("#dle-promt-text").val()
						$(this).dialog("close");
						$("#dlepopup").remove();
						if( callback ) callback( response );	
					}				
				};

	$("#dlepopup").remove();

	$("body").append("<div id='dlepopup' title='" + title + "' style='display:none'><br />"+ message +"<br /><br /><input type='text' name='dle-promt-text' id='dle-promt-text' class='ui-widget-content ui-corner-all' style='width:97%; padding: .4em;' value='" + d + "'/></div>");

	$('#dlepopup').dialog({
		autoOpen: true,
		width: 500,
		dialogClass: "modalfixed",
		buttons: b
	});

	$('.modalfixed.ui-dialog').css({position:"fixed"});
	$('#dlepopup').dialog( "option", "position", ['0','0'] );

	if (d.length > 0) {
		$("#dle-promt-text").select().focus();
	} else {
		$("#dle-promt-text").focus();
	}

};

var dle_user_profile = '';
var dle_user_profile_link = '';

function ShowPopupProfile( r, allowedit )
{
	var b = {};

	b[menu_profile] = function() { 
					document.location=dle_user_profile_link;						
			    };

	if (dle_group != 5) {

		b[menu_send] = function() { 
						document.location=dle_root + 'index.php?do=pm&doaction=newpm&username=' + dle_user_profile;						
				    };
	}

	if (allowedit == 1) {

		b[menu_uedit] = function() {
					$(this).dialog("close");

					var b1 = {};

					$('body').append('<div id="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #666666; opacity: .40;filter:Alpha(Opacity=40); z-index: 999; display:none;"></div>');
					$('#modal-overlay').css({'filter' : 'alpha(opacity=40)'}).fadeIn('slow');
				
					$("#dleuserpopup").remove();
					$("body").append("<div id='dleuserpopup' title='"+menu_uedit+"' style='display:none'></div>");

					b1[dle_act_lang[3]] = function() { 
											$(this).dialog("close");
											$("#dleuserpopup").remove();
							    };

					b1[dle_act_lang[4]] = function() { 
											document.getElementById('edituserframe').contentWindow.document.getElementById('saveuserform').submit();							
							    };
				
					$('#dleuserpopup').dialog({
						autoOpen: true,
						show: 'fade',
						width: 560,
						height: 500,
						dialogClass: "modalfixed",
						buttons: b1,
						open: function(event, ui) {
							$("#dleuserpopup").html("<iframe name='edituserframe' id='edituserframe' width='100%' height='400' src='" + dle_root + dle_admin + "?mod=editusers&action=edituser&user=" + dle_user_profile + "&skin=" + dle_skin + "' frameborder='0' marginwidth='0' marginheight='0' allowtransparency='true'></iframe>");
						},
						beforeClose: function(event, ui) { 
							$("#dleuserpopup").html("");
						},
						close: function(event, ui) {
								$('#modal-overlay').fadeOut('slow', function() {
						        $('#modal-overlay').remove();
						    });
						 }
					});
			
					if ($(window).width() > 830 && $(window).height() > 530 ) {
						$('.modalfixed.ui-dialog').css({position:"fixed"});
						$('#dleuserpopup').dialog( "option", "position", ['0','0'] );
					}

			    };

	}

	$("#dleprofilepopup").remove();

	$("body").append(r);

	$('#dleprofilepopup').dialog({
		autoOpen: true,
		show: 'fade',
		hide: 'fade',
		buttons: b,
		width: 450
	});
	
	return false;
};

function ShowProfile( name, url, allowedit )
{

	if (dle_user_profile == name && document.getElementById('dleprofilepopup')) {$('#dleprofilepopup').dialog('open');return false;}

	dle_user_profile = name;
	dle_user_profile_link = url;

	ShowLoading('');

	$.get(dle_root + "engine/ajax/profile.php", { name: name, skin: dle_skin }, function(data){

		HideLoading('');

		ShowPopupProfile( data, allowedit );

	});

	
	return false;
};

function FastSearch()
{
	$('#story').attr('autocomplete', 'off');
	$('#story').blur(function(){
		 	$('#searchsuggestions').fadeOut();
	});

	$('#story').keyup(function() {
		var inputString = $(this).val();

		if(inputString.length == 0) {
			$('#searchsuggestions').fadeOut();
		} else {

			if (dle_search_value != inputString && inputString.length > 3) {
				clearInterval(dle_search_delay);
				dle_search_delay = setInterval(function() { dle_do_search(inputString); }, 600);
			}

		}
	
	});
};

function dle_do_search( inputString )
{
	clearInterval(dle_search_delay);

	$('#searchsuggestions').remove();

	$("body").append("<div id='searchsuggestions' style='display:none'></div>");

	$.post(dle_root + "engine/ajax/search.php", {query: ""+inputString+""}, function(data) {
			$('#searchsuggestions').html(data).fadeIn().css({'position' : 'absolute', top:0, left:0}).position({
				my: "left top",
				at: "left bottom",
				of: "#story",
				collision: "fit flip"
			});
		});

	dle_search_value = inputString;

};

function ShowLoading( message )
{

	if ( message )
	{
		$("#loading-layer-text").html(message);
	}
		
	var setX = ( $(window).width()  - $("#loading-layer").width()  ) / 2;
	var setY = ( $(window).height() - $("#loading-layer").height() ) / 2;
			
	$("#loading-layer").css( {
		left : setX + "px",
		top : setY + "px",
		position : 'fixed',
		zIndex : '99'
	});
		
	$("#loading-layer").fadeTo('slow', 0.6);

};

function HideLoading( message )
{
	$("#loading-layer").fadeOut('slow');
};

function ShowAllVotes( )
{
	if (document.getElementById('dlevotespopup')) {$('#dlevotespopup').dialog('open');return false;}

	$.ajaxSetup({
	  cache: false
	});

	ShowLoading('');

	$.get(dle_root + "engine/ajax/allvotes.php?dle_skin=" + dle_skin, function(data){

		HideLoading('');
		$("#dlevotespopup").remove();	
	
		$("body").append( data );

		$(".dlevotebutton").button();

			$('#dlevotespopup').dialog({
				autoOpen: true,
				show: 'fade',
				hide: 'fade',
				width: 600,
				height: 150
			});

			if ($('#dlevotespopupcontent').height() > 400 ) {

				$('#dlevotespopupcontent').height(400);
				$('#dlevotespopup').dialog( "option", "height", $('#dlevotespopupcontent').height() + 40 );
				$('#dlevotespopup').dialog( "option", "position", 'center' );
			} else {

				$('#dlevotespopup').dialog( "option", "height", $('#dlevotespopupcontent').height() + 40 );
				$('#dlevotespopup').dialog( "option", "position", 'center' );

			}

	 });

	return false;
};

function fast_vote( vote_id )
{
	var vote_check = $('#vote_' + vote_id + ' input:radio[name=vote_check]:checked').val();

	ShowLoading('');

	$.get(dle_root + "engine/ajax/vote.php", { vote_id: vote_id, vote_action: "vote", vote_mode: "fast_vote", vote_check: vote_check, vote_skin: dle_skin }, function(data){

		HideLoading('');

		$("#dle-vote_list-" + vote_id).fadeOut(500, function() {
			$(this).html(data);
			$(this).fadeIn(500);
		});

	});

	return false;
};

function AddIgnorePM( id, text ){

    DLEconfirm( text, dle_confirm, function () {

		ShowLoading('');
	
		$.get(dle_root + "engine/ajax/pm.php", { id: id, action: "add_ignore", skin: dle_skin }, function(data){
	
			HideLoading('');
	
			DLEalert ( data, dle_info );
			return false;
		
	
		});

	} );
};

function DelIgnorePM( id, text ){

    DLEconfirm( text, dle_confirm, function () {

		ShowLoading('');
	
		$.get(dle_root + "engine/ajax/pm.php", { id: id, action: "del_ignore", skin: dle_skin }, function(data){
	
			HideLoading('');
	
			$("#dle-ignore-list-" + id).html('');
			DLEalert ( data, dle_info );
			return false;
		
	
		});

	} );
};
function dropdownmenu(obj, e, menucontents, menuwidth){

	if (window.event) event.cancelBubble=true;
	else if (e.stopPropagation) e.stopPropagation();

	var menudiv = $('#dropmenudiv');

	if (menudiv.is(':visible')) { clearhidemenu(); menudiv.fadeOut('fast'); return false; }

	menudiv.remove();

	$('body').append('<div id="dropmenudiv" style="display:none;position:absolute;z-index:100;width:165px;"></div>');

	menudiv = $('#dropmenudiv');

	menudiv.html(menucontents.join(""));

	if (menuwidth) menudiv.width(menuwidth);

	var windowx = $(document).width() - 30;
	var offset = $(obj).offset();

	if (windowx-offset.left < menudiv.width())
			offset.left = offset.left - (menudiv.width()-$(obj).width());

	menudiv.css( {
		left : offset.left + "px",
		top : offset.top+$(obj).height()+"px"
	});

	menudiv.fadeTo('fast', 0.9);

	menudiv.mouseenter(function(){
	      clearhidemenu();
	    }).mouseleave(function(){
	      delayhidemenu();
	});

	$(document).one("click", function() {
		hidemenu();
	});

	return false;
};

function hidemenu(e){
	$("#dropmenudiv").fadeOut("fast");
};

function delayhidemenu(){
	delayhide=setTimeout("hidemenu()",1000);
};

function clearhidemenu(){

	if (typeof delayhide!="undefined")
		clearTimeout(delayhide);
};

jQuery(function($){
		$(document).keydown(function(event){
		    if (event.which == 13 && event.ctrlKey) {
				if (window.getSelection) {
					var selectedText = window.getSelection();
				}
				else if (document.getSelection) {
					var selectedText = document.getSelection();
				}
				else if (document.selection) {
					var selectedText = document.selection.createRange().text;
				}

				if (selectedText == "" ) { return; }

				if (selectedText.toString().length > 255 ) {if ($.browser.mozilla) { alert(dle_big_text); } else {DLEalert(dle_big_text, dle_info);} return;}

				var b = {};
			
				b[dle_act_lang[3]] = function() { 
					$(this).dialog('close');						
				};
			
				b[dle_p_send] = function() { 
					if ( $('#dle-promt-text').val().length < 1) {
						$('#dle-promt-text').addClass('ui-state-error');
					} else {
						var response = $('#dle-promt-text').val();
						var selectedText = $('#orfom').text();
						$(this).dialog('close');

						$('#dlepopup').remove();

						$.post(dle_root + 'engine/ajax/complaint.php', { seltext: selectedText,  text: response, action: "orfo", url: window.location.href },
							function(data){
								if (data == 'ok') { if ($.browser.mozilla) { alert(dle_p_send_ok); } else { DLEalert(dle_p_send_ok, dle_info);} } else { if ($.browser.mozilla) { alert(data); } else { DLEalert(data, dle_info);} }
							});
			
					}				
				};
			
				$('#dlepopup').remove();
								
				$('body').append("<div id='dlepopup' title='"+dle_orfo_title+"' style='display:none'><br /><textarea name='dle-promt-text' id='dle-promt-text' class='ui-widget-content ui-corner-all' style='width:97%;height:80px; padding: .4em;'></textarea><div id='orfom' style='display:none'>"+selectedText+"</div></div>");
								
				$('#dlepopup').dialog({
					autoOpen: true,
					width: 550,
					dialogClass: "modalfixed",
					buttons: b
				});
			
				$('.modalfixed.ui-dialog').css({position:"fixed"});
				$('#dlepopup').dialog( "option", "position", ['0','0'] );


		    };
		});
});