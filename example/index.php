<?php

ini_set('display_errors', 1);

use FulgerX2007\GsmArena\GsmArenaApi;

require_once dirname(__DIR__) . '/GsmArena/GsmArenaApi.php';
$gsm = new GsmArenaApi();
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Widget Smartphone Specification</title>
    <link type="text/css" rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>
    <style>
        td {
            padding: 10px
        }

        a {
            color: #999;
            text-decoration: none;
            text-transform: uppercase
        }

        a:hover {
            text-decoration: none;
        }

        a.w {
            color: #999;
            text-decoration: none;
            font-size: 8px;
            text-transform: none
        }
    </style>
</head>
<body>
<div class="content">
    <table align="center">
        <tr>
            <td>
                Brand:
            </td>
            <td>
                <select id="brand" class="form-control">
                    <?php
                    $brands = $gsm->getBrands();
                    if ($brands['data']) {
                        foreach ($brands['data'] as $key => $brand) {
                            ?>
                            <option
                                    value="<?php print($key) ?>"
                                    data-name="<?php print $brand['title'] ?>"
                                    data-href="<?php print $brand['href'] ?>">
                                <?php print $brand['title'] ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                Keyword:
            </td>
            <td>
                <input type="text" placeholder="Asus Zenfone" id="query" value="Zenfone" class="form-control"/>
            </td>
        </tr>
        <tr>
            <td>
                Name:
            </td>
            <td>
                <select id="nama" class="form-control"></select>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:center">
                <input type="button" value="Ok" class="btn btn-success" id="ok"/>
                <input type="button" value="Export JSON" class="btn btn-info" id="export_json"/>
                <a href="#" type="button" class="btn btn-warning" id="export_devices_list"/>Export Devices List</a>
            </td>
        </tr>
    </table>
    <div id='spek' align="center"></div>
</div>

<script type="text/javascript" src="//code.jquery.com/jquery-2.2.0.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/js/bootstrap.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        let brand_id = $('#brand').val();
        let brand_name = $('#brand option[value=' + brand_id + ']').attr('data-name');
        $('#query').val(brand_name);
        cari(brand_name);
    });
    $('#query').on('change', function () {
        $('#nama').html('');
        let x = $('#query').val();
        cari(x);
    });
    $('#brand').on('change', function () {
        let brand_id = $('#brand').val();
        let brand_name = $('#brand option[value=' + brand_id + ']').attr('data-name');
        $('#query').val(brand_name);
        cari(brand_name);
    });

    $('body').on('click', '#export_json', function () {
        let x = $('#nama').val();
        if (x !== 'not found') {
            fetch('api.php?slug=' + x)
                .then(resp => resp.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    // the filename you want
                    a.download = x + '.json';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                })
                .catch(() => alert('oh no!'));
        } else {
            $('#spek').html('Not Found.');
        }
        return false;
    });

    $('body').on('click', '#ok', function () {
        $("#spek").html('<img src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/0.16.1/images/loader-large.gif" alt="Process.."/>');
        let x = $('#nama').val();
        if (x !== 'not found') {
            $.ajax({
                url: 'api.php?slug=' + x,
                crossDomain: true,
                dataType: 'json'
            }).done(function (b) {
                let c = '<h3>' + b.title + '</h3>';
                c += '<img src="' + b.img + '"/>';
                c += '<div class = "panel-group" id = "accordion">';
                $.each(b.data, function (index, value) {
                    c += '<div class = "panel panel-default">';
                    c += '	<div class = "panel-heading">';
                    c += '		<h4 class = "panel-title">';
                    c += '			<a data-toggle = "collapse" data-parent = "#accordion" href = "#collapse' + index.replace(' ', '') + '">';
                    c += index;
                    c += '			</a>';
                    c += '		</h4>';
                    c += '	</div>';
                    c += '	<div id = "collapse' + index.replace(' ', '') + '" class = "panel-collapse collapse">';
                    c += '		<div class = "panel-body">';
                    c += '			<table class = "table table-bordered">';
                    $.each(value, function (i, v) {
                        c += '			<tr><td><strong style="text-transform:uppercase">' + i.replace("_", " ") + '</strong></td><td>' + v + '</td></tr>';
                    });
                    c += '			</table>';
                    c += '		</div>';
                    c += '	</div>';
                    c += '</div>';
                });
                $('#spek').html(c);
            });
        } else {
            $('#spek').html('Not Found.');
        }
        return false;
    });

    $('body').on('click', '#export_devices_list', function () {
        let brand_title = $('#brand :selected').text().trim();
        $("#spek").html('<img src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/0.16.1/images/loader-large.gif" alt="Process.."/>');
        let informations = '';
        let anchor = document.querySelector('a');
        $("#nama option").each(function () {
            informations += $(this).text() + "\n";
        });
        console.log(informations);
        anchor.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(informations);
        anchor.download = brand_title + '.txt';
        $('#spek').html('success');
    });

    function cari(d) {
        $.ajax({
            url: 'api.php?query=' + d,
            crossDomain: true,
            dataType: 'json'
        }).done(function (b) {
            let c = '';
            if (b.status !== 'error') {
                $.each(b.data, function (i, a) {
                    c += '<option value="' + b.data[i].slug + '">' + b.data[i].title + '</option>';
                });
            } else {
                c += '<option value="not found">Not Found</option>';
            }
            $('#nama').html(c);
        });
    }
</script>
</body>
</html>
