function loadMore() {
    var arg = '';
    var append = false;
    var begin = $('#begin').val();
    var end = $('#end').val();
    var duration = $('#duration').val();
    var type = $('#type').val();
    var number = $('#number').val();
    var classs = $('#class').val();
    var ip = $('#called').val();

    if (last > 0) {
        arg += 'last=' + last.toString();
        append = true;
    }

    if (append) {
        arg += '&begin=' + begin;
        append = true;
    } else {
        arg += 'begin=' + begin;
    }

    if (append) {
        arg += '&end=' + end;
        append = true;
    } else {
        arg += 'end=' + end;
    }

    if (append) {
        arg += '&type=' + type;
        append = true;
    } else {
        arg += 'type=' + type;
    }

    if (append) {
        arg += '&number=' + number;
        append = true;
    } else {
        arg += 'number=' + number;
    }

    if (append) {
        arg += '&duration=' + duration;
        append = true;
    } else {
        arg += 'duration=' + duration;
    }

    if (append) {
        arg += '&class=' + classs;
        append = true;
    } else {
        arg += 'class=' + classs;
    }

    if (append) {
        arg += '&ip=' + ip;
        append = true;
    } else {
        arg += 'ip=' + ip;
    }

    $.get('/cdr/ajxquery?' + arg, function (resp, status) {
            var obj = resp;
            
            if (obj.status != 200) {
                alert('Error: ' + obj.status + ' ' + obj.message);
                return;
            }
            
            last = obj.last;
            for (var i in obj.data) {
                var text = '<tr>' +
                    '<td>' + obj.data[i].id + '</td>' +
                    '<td>' + obj.data[i].caller + '</td>' +
                    '<td>' + obj.data[i].called + '</td>' +
                    '<td>' + obj.data[i].location + '</td>' +
                    '<td>' + getForSeconds(obj.data[i].duration) + '</td>' +
                    '<td>' + long2ip(obj.data[i].src_ip) + '</td>' +
                    '<td>' + long2ip(obj.data[i].dst_ip) + '</td>' +
                    '<td>' + obj.data[i].create_time + '</td>' +
                    '<td><a href="javascript:void(0);" res="' + obj.data[i].file + '" onClick="show(this)"><span class="glyphicon glyphicon-headphones" aria-hidden="true"></span> 试 听</a></td>' +
                    '<td><span class="glyphicon glyphicon-qrcode" res="' + obj.data[i].file + '" onClick="qrcode(this)"></span></td>' +
                    '<td><a href="/record/' + obj.data[i].file + '">下 载</a></td></tr>';
                $(text).appendTo("#data");
            }
            if (obj.data.length < 32) {
                $("#loading").css("display","none");
            }
        });
}

function getForSeconds(totalSeconds) {  
    if (totalSeconds < 86400) {  
        var dt = new Date("01/01/2000 0:00");  
        dt.setSeconds(totalSeconds);  
        return formatForDate(dt);  
    } else {  
        return null;  
    }  
}  

function formatForDate(dt) {  
    var h = dt.getHours(),  
        m = dt.getMinutes(),  
        s = dt.getSeconds(),  
        r = "";  
    if (h > 0) {  
        r += (h > 9 ? h.toString() : "0" + h.toString()) + ":";  
    } else {
        r += "00:";
    }
    r += (m > 9 ? m.toString() : "0" + m.toString()) + ":"  
    r += (s > 9 ? s.toString() : "0" + s.toString());  
    return r;  
}

function long2ip(num){
    num = parseInt(num);
    if(num > 0 && num < 0xffffffff){
        return (num >>> 24) + "." + (num >> 16 & 0xff) + "." + (num >> 8 & 0xff) + "." + (num & 0xff);
    }
    return "0.0.0.0";
}
