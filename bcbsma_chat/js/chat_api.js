(function($, Drupal, drupalSettings) {
  Drupal.behaviors.chatApi = {
    attach: function (context, settings) {
      $("#live-chat .chatwindowTiggers div").keypress(function (e) {
        if (e.keyCode == 13) {
          $(this).click();
        }
      });
      var chatApi = drupalSettings.chatApi;
      // You can init map here.
      let chatPageUrl = drupalSettings.chat_api.chatApi.chatpageurl;
      let currentUrlpath = window.location.pathname;
      if (jQuery.inArray(currentUrlpath, chatPageUrl[0]) !== -1) {
         $(once('div', '#live-chat')).each(function () {
  		      $.ajax ({
        	     url: '/chatavailability/check',
        	     type: 'GET',
        	     datatype: 'JSON',
        	     success: function (data) {
            	   if(data.chat_status == "success"){
                   $('#live-chat').attr('available', true);
                }
        	    }
      	    });
          })
        }
     }
  };
})(jQuery, Drupal, drupalSettings);
 // Js File for Chat
 var egainChat = {};
 egainChat.egainChatParameters = {};
 //Set to true to enable posting attributes to templates.
 egainChat.postChatAttributes  = false;
 egainChat.eglvchathandle = null;
 egainChat.liveServerURL = drupalSettings.chat_api.chatApi.chatBaseUrl;
 egainChat.openHelp = function(queue) {
    //var queue = getRadioButtonValue(document.myform.selection);
    var domainRegex = /^((?:https?:\/\/)?(?:www\.)?([^\/]+))/i;
    try{
        if( egainChat.eglvchathandle != null && egainChat.eglvchathandle.closed == false ){
            egainChat.eglvchathandle.focus();
            return;
        }
    }
    catch(err){}
    var refererName = "";
    refererName = encodeURIComponent(refererName);
    var refererurl = encodeURIComponent(document.location.href);
    var hashIndex = refererurl.lastIndexOf('#');
    if(hashIndex != -1){
        refererurl = refererurl.substring(0,hashIndex);
    }
    var eglvcaseid = (/eglvcaseid=[0-9]*/gi).exec(window.location.search);
    var vhtIds = '';
    if(typeof EGAINCLOUD != "undefined" && EGAINCLOUD.Account.getAllIds)
    {
        var ids = EGAINCLOUD.Account.getAllIds();
        vhtIds = '&aId=' + ids.a + '&sId=' + ids.s +'&uId=' + ids.u;
    }
    var EG_CALL_Q = window.EG_CALL_Q || [];
    EG_CALL_Q.push( ["enableTracker", true] );
    var eGainChatUrl = drupalSettings.chat_api.chatApi.chatTemplateUrl;
    var domain = domainRegex.exec(eGainChatUrl)[0];
    if( window.navigator.userAgent.indexOf("Trident") != -1 && egainChat.postChatAttributes ) {
        var win = document.getElementById('egainChatDomainFrame');
        win.contentWindow.postMessage(JSON.stringify(egainChat.egainChatParameters), domain);
    }
    if( (eGainChatUrl + refererurl).length <= 2000)
        eGainChatUrl += '&referer='+refererurl;
    var params = "height=650,width=450,resizable=yes,scrollbars=yes,toolbar=no";
    egainChat.eglvchathandle = window.open( eGainChatUrl,'',params)
    /*Message posted to the child window every second until it sends a message in return. This is done as we can not be sure when the mssage listener will be set in the child window.*/
    if( window.navigator.userAgent.indexOf("Trident") == -1 && egainChat.postChatAttributes ) {
        var messagePostInterval = setInterval(function(){
            var message = egainChat.egainChatParameters;
            egainChat.eglvchathandle.postMessage(message, domain);
        },1000);
        window.addEventListener('message',function(event) {
            if(event.data.chatParametersReceived) {
                clearInterval(messagePostInterval);
            }
        },false);
    }
  }
  /*To be called by client website. All the parameters specified in eGainLiveConfig must be set here.*/
  egainChat.storeChatParameters = function(attributeName, attributeValue) {
    egainChat.egainChatParameters[attributeName] = attributeValue;
  }
  egainChat.writeIframeIfRequired = function() {
    if(egainChat.postChatAttributes  &&  window.navigator.userAgent.indexOf("Trident") != -1 ) {
        var iframe = document.createElement('iframe');
        iframe.src=egainChat.liveServerURL+'/web/view/live/customer/storeparams.html';
        iframe.style.display = 'none';
        iframe.name = 'egainChatDomainFrame';
        iframe.id = 'egainChatDomainFrame';
        document.body.appendChild(iframe);
    }
  }
  egainChat.writeIframeIfRequired();
