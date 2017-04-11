/*
 *
 *  jQuery $.getImageData Plugin 0.3
 *  http://www.maxnov.com/getimagedata
 *
 *  Written by Max Novakovic (http://www.maxnov.com/)
 *  Date: Thu Jan 13 2011
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *  Includes jQuery JSONP Core Plugin 2.4.0 (2012-08-21)
 *  https://github.com/jaubourg/jquery-jsonp
 *  Copyright 2012, Julian Aubourg
 *  Released under the MIT License.
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *  Copyright 2011, Max Novakovic
 *  Dual licensed under the MIT or GPL Version 2 licenses.
 *  http://www.maxnov.com/getimagedata/#license
 *
 */
(function(d){function U(){}function V(a){r=[a]}function e(a,d,e){return a&&a.apply(d.context||d,e)}function g(a){function g(b){l++||(m(),n&&(t[c]={s:[b]}),A&&(b=A.apply(a,[b])),e(u,a,[b,B,a]),e(C,a,[a,B]))}function s(b){l++||(m(),n&&b!=D&&(t[c]=b),e(v,a,[a,b]),e(C,a,[a,b]))}a=d.extend({},E,a);var u=a.success,v=a.error,C=a.complete,A=a.dataFilter,p=a.callbackParameter,F=a.callback,W=a.cache,n=a.pageCache,G=a.charset,c=a.url,f=a.data,H=a.timeout,q,l=0,m=U,b,h,w;I&&I(function(a){a.done(u).fail(v);u=a.resolve;v=a.reject}).promise(a);a.abort=function(){!l++&&m()};if(!1===e(a.beforeSend,a,[a])||l)return a;c=c||x;f=f?"string"==typeof f?f:d.param(f,a.traditional):x;c+=f?(/\?/.test(c)?"&":"?")+f:x;p&&(c+=(/\?/.test(c)?"&":"?")+encodeURIComponent(p)+"=?");W||n||(c+=(/\?/.test(c)?"&":"?")+"_"+(new Date).getTime()+"=");c=c.replace(/=\?(&|$)/,"="+F+"$1");n&&(q=t[c])?q.s?g(q.s[0]):s(q):(J[F]=V,b=d(K)[0],b.id=L+X++,G&&(b[Y]=G),M&&11.6>M.version()?(h=d(K)[0]).text="document.getElementById('"+b.id+"')."+y+"()":b[N]=N,Z&&(b.htmlFor=b.id,b.event=z),b[O]=b[y]=b[P]=function(a){if(!b[Q]||!/i/.test(b[Q])){try{b[z]&&b[z]()}catch(c){}a=r;r=0;a?g(a[0]):s(R)}},b.src=c,m=function(a){w&&clearTimeout(w);b[P]=b[O]=b[y]=null;k[S](b);h&&k[S](h)},k[T](b,p=k.firstChild),h&&k[T](h,p),w=0<H&&setTimeout(function(){s(D)},H));return a}var N="async",Y="charset",x="",R="error",T="insertBefore",L="_jqjsp",z="onclick",y="on"+R,O="onload",P="onreadystatechange",Q="readyState",S="removeChild",K="<script>",B="success",D="timeout",J=window,I=d.Deferred,k=d("head")[0]||document.documentElement,t={},X=0,r,E={callback:L,url:location.href},M=J.opera,Z=!!d("<div>").html("<!--[if IE]><i><![endif]-->").find("i").length;g.setup=function(a){d.extend(E,a)};d.jsonp=g})(jQuery);(function($){$.getImageData=function(args){var regex_url_test=/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;if(args.url){var is_secure=location.protocol==="https:";var server_url="";if(args.server&&regex_url_test.test(args.server)&&!(is_secure&&args.server.indexOf("http:")==0)){server_url=args.server}else server_url="//img-to-json.appspot.com/";server_url+="?callback=?";$.jsonp({url:server_url,data:{url:escape(args.url)},dataType:"jsonp",timeout:args.timeout||1e4,success:function(data,status){var return_image=new Image;$(return_image).load(function(){this.width=data.width;this.height=data.height;if(typeof args.success==typeof Function){args.success(this)}}).attr("src",data.data)},error:function(xhr,text_status){if(typeof args.error==typeof Function){args.error(xhr,text_status)}}})}else{if(typeof args.error==typeof Function){args.error(null,"no_url")}}}})(jQuery);
