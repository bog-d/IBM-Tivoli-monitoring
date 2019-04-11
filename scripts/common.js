$(function() {
    $("#to_remove").remove();
})

function setCookie(cname,cvalue,exmins) {
    var d = new Date();
    d.setTime(d.getTime() + (exmins*60*1000));
    var expires = "expires=" + d.toGMTString();
    document.cookie = cname+"="+cvalue+"; "+expires;
}

function clearCookie() {
    setCookie("username", "", -1);
    setCookie("acsuser", "", -1);
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i].trim();
        if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
    }
    return "";
}

$(function() {
    $('.popup .close_window, .overlay').click(function (){
        $('.popup, .overlay').css({'opacity':'0', 'visibility':'hidden'});
    });
    $('a.open_window').click(function (e){
        $('.popup, .overlay').css({'opacity':'1', 'visibility':'visible'});
        e.preventDefault();
    });
});

$(function() {
    $('#authIdBtn_input').click(function() {
            $.ajax({
                type: "POST",
                url: "users_auth.php",
                dataType: 'json',
                data: {code: $("#code_input").val()},
                cache: false,
                success: function (jsondata) {
                    // console.log(jsondata);
                    if (jsondata.reset) {
                        clearCookie();
                        $("#code_reset_area").attr('hidden', 'hidden');
                        $("#code_input_area").removeAttr('hidden');
                    }
                    else {
                        $("#code_input_area").attr('hidden', 'hidden');
                        $("#code_reset_area").removeAttr('hidden')
                    }
                    if (jsondata.btn_user)
                        $(".btn_user").removeAttr('disabled');
                    else
                        $(".btn_user").attr('disabled', 'disabled');
                    if (jsondata.btn_form)
                        $(".btn_form").removeAttr('disabled');
                    else
                        $(".btn_form").attr('disabled', 'disabled');
                    if (jsondata.btn_admin)
                        $(".btn_admin").removeAttr('disabled');
                    else
                        $(".btn_admin").attr('disabled', 'disabled');
                    if (jsondata.btn_admin)
                        $(".update_data").removeAttr('hidden');
                    else
                        $(".update_data").attr('hidden', 'hidden');
                    $("#code_title_area").html(jsondata.title);
                    $("#output_area").html(jsondata.output);
                },
                error: function (req, text, error) {
                    console.log('Ошибка! ' + text + ' | ' + error);
                }
            });
        }
    );
});

$(function() {
    $('#authIdBtn_reset').click(function() {
            $.ajax({
                type: "POST",
                url: "users_auth.php",
                dataType: 'json',
                data: {code: $("#code_reset").val()},
                cache: false,
                success: function (jsondata) {
                    // console.log(jsondata);
                    if (jsondata.reset) {
                        clearCookie();
                        $("#code_reset_area").attr('hidden', 'hidden');
                        $("#code_input_area").removeAttr('hidden');
                    }
                    else {
                        $("#code_input_area").attr('hidden', 'hidden');
                        $("#code_reset_area").removeAttr('hidden')
                    }
                    if (jsondata.btn_user)
                        $(".btn_user").removeAttr('disabled');
                    else
                        $(".btn_user").attr('disabled', 'disabled');
                    if (jsondata.btn_form)
                        $(".btn_form").removeAttr('disabled');
                    else
                        $(".btn_form").attr('disabled', 'disabled');
                    if (jsondata.btn_admin)
                        $(".btn_admin").removeAttr('disabled');
                    else
                        $(".btn_admin").attr('disabled', 'disabled');
                    if (jsondata.btn_admin)
                        $(".update_data").removeAttr('hidden');
                    else
                        $(".update_data").attr('hidden', 'hidden');
                    $("#output_area").html(jsondata.output);
                },
                error: function (req, text, error) {
                    console.log('Ошибка! ' + text + ' | ' + error);
                }
            });
        }
    );
});


