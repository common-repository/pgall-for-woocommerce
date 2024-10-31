jQuery(function(o){"use strict";function e(){this.$form=null,this.init=function(t,e){this.$form=t,o.fn.payment_complete=this.payment_complete,o.fn.payment_fail=this.payment_fail,o("body").on("inicis_unblock_payment",f.unblock),this.$form.on(e,this.process_payment)},this.payment_complete=function(t){window.location.href=t},this.payment_cancel=function(){o("#pafw_payment_form").remove(),f.unblock()},this.payment_fail=function(t){alert(t),o("#pafw_payment_form").remove(),f.unblock()},this.process_payment=function(t,e){_.isUndefined(e.redirect_url)||_.isEmpty(e.redirect_url)?(document.getElementById("pafw_payment_form")||o(document.body).append('<div id="pafw_payment_form" style="width: 100%;height: 100%;position: fixed;top: 0;z-index: 99999;"></div>'),o("#pafw_payment_form").empty().append(e.payment_form)):window.location.href=e.redirect_url}}function n(){this.$form=null,this.init=function(t,e){this.$form=t,o.fn.payment_complete=this.payment_complete,o.fn.payment_fail=this.payment_fail,void 0===o.fn.pafw_kakaopay_return&&(o.fn.pafw_kakaopay_return=this.unblock.bind(this)),d.on(e,this.process_payment.bind(this))},this.payment_complete=function(t){window.location.href=t},this.payment_cancel=function(){o("#pafw_payment_form").remove(),f.unblock()},this.payment_fail=function(t){alert(t),o("#pafw_payment_form").remove(),f.unblock()},this.unblock=function(t){t&&alert(t),f.unblock()},this.process_payment=function(t,e){_.isUndefined(e.redirect_url)||_.isEmpty(e.redirect_url)?(document.getElementById("pafw_payment_form")||o(document.body).append('<div id="pafw_payment_form" style="width: 100%;height: 100%;position: fixed;top: 0;z-index: 99999;"></div>'),o("#pafw_payment_form").empty().append(e.payment_form)):window.location.href=e.redirect_url}}function i(){this.$form=null,this.init=function(t,e){this.$form=t,o.fn.payment_complete=this.payment_complete,o.fn.payment_fail=this.payment_fail,void 0===o.fn.pafw_kakaopay_return&&(o.fn.pafw_kakaopay_return=this.unblock.bind(this)),d.on(e,this.process_payment.bind(this))},this.payment_complete=function(t){window.location.href=t},this.payment_cancel=function(){o("#pafw_payment_form").remove(),f.unblock()},this.payment_fail=function(t){alert(t),o("#pafw_payment_form").remove(),f.unblock()},this.unblock=function(t){t&&alert(t),f.unblock()},this.process_payment=function(t,e){_.isUndefined(e.redirect_url)||_.isEmpty(e.redirect_url)?(document.getElementById("pafw_payment_form")||o(document.body).append('<div id="pafw_payment_form" style="width: 100%;height: 100%;position: fixed;top: 0;z-index: 99999;"></div>'),o("#pafw_payment_form").empty().append(e.payment_form)):window.location.href=e.redirect_url}}function a(){this.$form=null,this.$wrapper=o(".pafw-payment-method-item.nicepay"),this.$placeholder=o(".pafw-not-registered.custom-handler",this.$wrapper),this.$card_form=o(".pafw_card_form",this.$wrapper),this.$card_info=o(".pafw-registered",this.$wrapper),this.init=function(t,e){this.$form=t,o(".pafw-button.card-action.register",this.$wrapper).off("click").on("click",this.re_register_payment_method.bind(this)),this.$form.on(e,this.process_payment.bind(this)),this.$placeholder.on("click",this.show_register_form.bind(this))},this.show_register_form=function(){o(".pafw-payment-method-info",this.$wrapper).css("padding","5px"),this.$placeholder.css("display","none"),this.$card_info.css("display","none"),this.$card_form.css("display","block")},this.re_register_payment_method=function(){confirm("결제수단을 다시 등록하시겠습니까?")&&this.show_register_form()},this.process_payment=function(t,e){window.location.reload()}}function s(){this.$form=null,this.$wrapper=o(".pafw-payment-method-item.settlepg"),this.$placeholder=o(".pafw-not-registered.custom-handler",this.$wrapper),this.$card_form=o(".pafw_card_form",this.$wrapper),this.$card_info=o(".pafw-registered",this.$wrapper),this.init=function(t,e){this.$form=t,o(".pafw-button.card-action.register",this.$wrapper).off("click").on("click",this.re_register_payment_method.bind(this)),this.$form.on(e,this.process_payment.bind(this)),this.$placeholder.on("click",this.show_register_form.bind(this))},this.show_register_form=function(){o(".pafw-payment-method-info",this.$wrapper).css("padding","5px"),this.$placeholder.css("display","none"),this.$card_info.css("display","none"),this.$card_form.css("display","block")},this.re_register_payment_method=function(){confirm("결제수단을 다시 등록하시겠습니까?")&&this.show_register_form()},this.process_payment=function(t,e){window.location.reload()}}function r(){this.$form=null,this.oPay=null,this.init=function(t,e){this.$form=t,this.$form.on(e,this.process_payment.bind(this))},this.get_npay_object=function(t){return this.oPay=null,this.oPay=Naver.Pay.create({mode:_pafw_npay.mode,clientId:_pafw_npay.client_id,openType:_pafw_npay.open_type,payType:t.data.pay_type}),this.oPay},this.process_payment=function(t,e){_.isUndefined(e.redirect_url)||_.isEmpty(e.redirect_url)?(document.getElementById("pafw_payment_form")||o(document.body).append('<div id="pafw_payment_form" style="width: 100%;height: 100%;position: fixed;top: 0;z-index: 99999;"></div>'),o("#pafw_payment_form").empty().append(e.payment_form)):window.location.href=e.redirect_url}}function p(){this.$form=null,this.$wrapper=o(".pafw-payment-method-item.innopay"),this.$placeholder=o(".pafw-not-registered.custom-handler",this.$wrapper),this.$card_form=o(".pafw_card_form",this.$wrapper),this.$card_info=o(".pafw-registered",this.$wrapper),this.init=function(t,e){this.$form=t,o(".pafw-button.card-action.register",this.$wrapper).off("click").on("click",this.re_register_payment_method.bind(this)),this.$form.on(e,this.process_payment.bind(this)),this.$placeholder.on("click",this.show_register_form.bind(this))},this.show_register_form=function(){o(".pafw-payment-method-info",this.$wrapper).css("padding","5px"),this.$placeholder.css("display","none"),this.$card_info.css("display","none"),this.$card_form.css("display","block")},this.re_register_payment_method=function(){confirm("결제수단을 다시 등록하시겠습니까?")&&this.show_register_form()},this.process_payment=function(t,e){window.location.reload()}}function c(){this.$form=null,this.$wrapper=o(".pafw-payment-method-item.lguplus"),this.$placeholder=o(".pafw-not-registered.custom-handler",this.$wrapper),this.$card_form=o(".pafw_card_form",this.$wrapper),this.$card_info=o(".pafw-registered",this.$wrapper),this.init=function(t,e){this.$form=t,o(".pafw-button.card-action.register",this.$wrapper).off("click").on("click",this.re_register_payment_method.bind(this)),this.$form.on(e,this.process_payment.bind(this)),this.$placeholder.on("click",this.show_register_form.bind(this))},this.show_register_form=function(){o(".pafw-payment-method-info",this.$wrapper).css("padding","5px"),this.$placeholder.css("display","none"),this.$card_info.css("display","none"),this.$card_form.css("display","block")},this.re_register_payment_method=function(){confirm("결제수단을 다시 등록하시겠습니까?")&&this.show_register_form()},this.process_payment=function(t,e){window.location.reload()}}function m(){this.$form=null,this.init=function(t,e){this.$form=t,o.fn.payment_complete=this.payment_complete,o.fn.payment_fail=this.payment_fail,this.$form.on(e,this.process_payment)},this.payment_complete=function(t){window.location.href=t},this.payment_cancel=function(){o("#pafw_payment_form").remove(),f.unblock()},this.payment_fail=function(t){alert(t),o("#pafw_payment_form").remove(),f.unblock()},this.process_payment=function(t,e){_.isUndefined(e.redirect_url)||_.isEmpty(e.redirect_url)?(document.getElementById("pafw_payment_form")||o(document.body).append('<div id="pafw_payment_form" style="width: 100%;height: 100%;position: fixed;top: 0;z-index: 99999;"></div>'),o("#pafw_payment_form").empty().append(e.payment_form)):window.location.href=e.redirect_url}}var d=o("div.pafw-payment-methods"),f=(0===(d=0===d.length?o("form#add_payment_method"):d).length&&(d=o(".pafw-payment-methods-block")),{$wrappers:[],init:function(){},is_blocked:function(t){return t.is(".processing")||t.parents(".processing").length},block:function(t){f.is_blocked(t)||(t.addClass("processing").block({message:null,overlayCSS:{background:"#fff",opacity:.6}}),f.$wrappers.push(t))},unblock:function(){_.each(f.$wrappers,function(t,e){t.removeClass("processing").unblock()}),f.$wrappers=[]}});o.fn.pafw_block_controller=f,o.fn.pafw_hook={hooks:[],add_filter:function(t,e){void 0===o.fn.pafw_hook.hooks[t]&&(o.fn.pafw_hook.hooks[t]=[]),o.fn.pafw_hook.hooks[t].push(e)},apply_filters:function(t,e){if(void 0!==o.fn.pafw_hook.hooks[t])for(var n=0;n<o.fn.pafw_hook.hooks[t].length;++n)e[0]=o.fn.pafw_hook.hooks[t][n](e);return e[0]}},window.addEventListener("message",function(t){t.origin===_pafw.gateway_domain&&("pafw_cancel_payment"===t.data.action||"pafw_payment_fail"===t.data.action?(o("#pafw_payment_form").remove(),f.unblock()):"pafw_payment_complete"===t.data.action&&o.fn.payment_complete(t.data.redirect_url))});o("body").trigger("pafw_init_hook"),(new function(){this.$payment_methods_wrapper=null,this.gateways=null,this.init=function(t){this.$payment_methods_wrapper=t;t={inicis:new e,kakaopay:new n,nicepay:new a,npay:new r,settlebank:new i,settlepg:new s,innopay:new p,lguplus:new c,tosspayments:new m};this.gateway_object=o.fn.pafw_hook.apply_filters("pafw_gateway_objects",[t]),o(".pafw-button.card-action.register").on("click",this.re_register_payment_method.bind(this)),o(".pafw-button.card-action.delete").on("click",this.delete_payment_method.bind(this)),_.each(_pafw.gateway,function(t,e){void 0!==this.gateway_object[e]&&(t=_.values(t).map(function(t){return"register_payment_methods_"+t}).join(" "),this.gateway_object[e].init(this.$payment_methods_wrapper,t))}.bind(this)),o("form#add_payment_method").on("submit",function(t){var e=o("input[name=payment_method]:checked").val();return-1===["tosspayments_subscription","inicis_subscription","kakaopay_subscription","settlebank_mybank_subscription"].indexOf(e)||(t.preventDefault(),t.stopPropagation(),this.add_payment_methods(),!1)}.bind(this)),o(document).on("fragment_updated",function(){o(".register-token").on("click",this.maybeAddPaymentMethod.bind(this)),o(".pafw-token-payment-fields .register-card").on("click",this.addKeyInPaymentMethod.bind(this)),void 0!==o.magnificPopup&&o(".pafw-token-payment-fields .close").on("click",o.magnificPopup.close)}.bind(this)),o(".register-token").on("click",this.maybeAddPaymentMethod.bind(this)),o(".pafw-token-payment-fields .register-card").on("click",this.addKeyInPaymentMethod.bind(this)),void 0!==o.magnificPopup&&o(".pafw-token-payment-fields .close").on("click",o.magnificPopup.close)},this.maybeAddPaymentMethod=function(t){var e=o(t.target).data("payment_method");-1!==["tosspayments_subscription","inicis_subscription","kakaopay_subscription","settlebank_mybank_subscription"].indexOf(e)?this.add_payment_methods(t,e):-1!==["nicepay_subscription","settlepg_subscription"].indexOf(e)&&o.magnificPopup.open({items:{src:o(".pafw-token-payment-fields",o(t.target)),type:"inline",midClick:!0},midClick:!0,closeBtnInside:!1,showCloseBtn:!1,fixedContentPos:!0})},this.re_register_payment_method=function(t){confirm("결제수단을 다시 등록하시겠습니까?")&&this.register_payment_method(t)},this.add_payment_methods=function(t,e){var n=this;if(f.is_blocked(n.$payment_methods_wrapper))return!1;f.block(n.$payment_methods_wrapper);var i=o("input[name=payment_method]:checked").val();_.isUndefined(e)||(i=e),o.ajax({type:"POST",url:pafw_ajaxurl,data:{action:_pafw.slug+"-pafw_ajax_action",payment_method:i,redirect_url:"undefined"!=typeof _pafw_token?_pafw_token.redirect_url:"",payment_action:"add_payment_method"},success:function(t){t&&t.success?n.$payment_methods_wrapper.trigger("register_payment_methods_"+i,t.data):(alert(t.data),f.unblock())}})},this.addKeyInPaymentMethod=function(t){const e=o(t.target).closest(".pafw-token-payment-fields"),n=_.filter(_.unique(_.pluck(o("input",o(".pafw-token-payment-fields")),"name"))),i={};if(f.is_blocked(e))return!1;f.block(e),_.each(n,function(t){var e="input[name="+t+"]";"radio"===o(e).attr("type")?i[t]=o(e+":checked").val():i[t]=o(e).val()}),o.ajax({type:"POST",url:pafw_ajaxurl,data:_.extend({action:_pafw.slug+"-pafw_ajax_action",payment_method:e.data("payment_method"),redirect_url:"undefined"!=typeof _pafw_token?_pafw_token.redirect_url:"",payment_action:"add_payment_method"},i),success:function(t){t&&t.success?window.location.reload():(alert(t.data),f.unblock())}})},this.register_payment_method=function(t){var e=this;if(f.is_blocked(e.$payment_methods_wrapper))return!1;f.block(e.$payment_methods_wrapper);var n=o(t.target).data("payment_method");o.ajax({type:"POST",url:pafw_ajaxurl,data:{action:_pafw.slug+"-pafw_ajax_action",payment_method:n,payment_action:"register_payment_method",params:o(t.target).closest("form").serialize()},success:function(t){t&&t.success?e.$payment_methods_wrapper.trigger("register_payment_methods_"+n,t.data):(alert(t.data),f.unblock())}})},this.delete_payment_method=function(t){if(confirm("등록하신 결제수단을 삭제하시겠습니까?")){if(f.is_blocked(this.$payment_methods_wrapper))return!1;f.block(this.$payment_methods_wrapper);t=o(t.target).data("payment_method");o.ajax({type:"POST",url:pafw_ajaxurl,data:{action:_pafw.slug+"-pafw_ajax_action",payment_method:t,payment_action:"delete_payment_method"},success:function(t){t&&t.success?window.location.reload():(alert(t.data),f.unblock())}})}}}).init(d)});