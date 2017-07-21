// Wait until DOM is ready
$(document).ready (function () {

    // Tabs
    $('#tabs a').click(function (e) {
      e.preventDefault()
      $(this).tab('show')
    })


    // Get common DOM objects
    var xhr;
    var body = $('body');
    var main = $('main');
    var form = $('form');
    var field_file = form.find ('[name="file"]')[0];
    var submit = form.find ('[type="submit"]');
    var table = $('table');
    var configuration = $('[name="configuration"]');
    
    
    // Toggle filter
    $('.toggle-filter-action').click (function (e) {
        e.preventDefault ();
        e.stopPropagation ();
        body.toggleClass ('state-toggled');
    });
    
    
    $('[name="dictionary"]').change (function (e) {
        var self = $(this);
        var dictionary = self.find ('option:selected').attr ('value');
        
        console.log (dictionary);
        
        configuration.prop ('disabled', dictionary != "");
        
    });
    
    
    $('.update-config-action').click (function (e) {
        $.ajax ({
            method: 'POST',
            url: 'process-header.php', 
            dataType: "html",
            data: {
                configuration: configuration.val ()
            },
            success: function (html) {
                table.replaceWith (html);
                table = $('table');
                $('#config').modal('hide');
            }
        });
    });
    
    
    // Download config
    $('.store-config-action').click (function (e) {
        $.ajax ({
            url: 'download-config.php',
            type: 'POST',
            dataType: "text",
            data: {
                configuration: configuration.val ()
            },
            success: function (result) {
                
                var blob = new Blob([result], {type: 'text/plain'});
                var link = document.createElement('a');
                
                link.setAttribute ('href', window.URL.createObjectURL (blob));
                link.setAttribute ('download', 'config-umutextstats.xml');
                link.dataset.downloadurl = ['text/plain', link.download, link.href].join(':');
                link.draggable = true;
                link.classList.add ('dragout');


                link.click();
            }
        });
    });
    
    
    /*
    var backup;
    $('#config')
        .on ('hidden.bs.modal', function (e) {

        })
        .on ('show.bs.modal', function (e) {
            
        })
    ;
    */
    
    
    // Handle submit
    form.submit (function (e) {
    
        // Prevent default
        e.preventDefault ();
        
        
        // Cancel previous response
        if (xhr) {
            xhr.abort ();
        }
        
        
        // Disable controls
        body.addClass ('loading-state');
        
        
        /**
         * send_request
         *
         */
        var send_request = function (data) {
        
            // Get file
            xhr = $.ajax ({
                method: 'POST',
                url: 'process.php', 
                dataType: "html",
                data: data,
                success: function (html) {
                    table.find ('tbody').html (html);
                },
                complete: function (response) {
                    body.removeClass ('loading-state');
                    body.removeClass ('state-toggled');
                }
            });
        
        }
        
        
        // Fetch data
        var data = {
            configuration: configuration.val ()
        };
        
        if ($(".twitter-tab-wrapper").hasClass ('active')) {
            data.query = form.find ('[name="query"]').val ();
            send_request (data);
        }
        
        if ($(".text-tab-wrapper").hasClass ('active')) {
            data.content = form.find ('[name="content"]').val ();
            send_request (data);
        }
        
        if ($(".file-tab-wrapper").hasClass ('active')) {
            if (field_file.files.length == 1) {
            
                var reader = new FileReader ();
                reader.readAsDataURL (field_file.files[0]);
                reader.onload = function () {
                    data.file = reader.result;
                    send_request (data);
                
                };
                reader.onerror = function (error) {
                };
            }
        }
        
        
        // Prevent submit returning false to allow only
        // Ajax validation
        return false;
    });

    
    $('.export-csv-action').click (function () {
        
        var headers = $("thead th span", table).map (function () {return $.trim(this.innerHTML);}).get()

        var rows = $("tbody > tr", table).map (function () { 
            return [$("td span", this).map (function () { 
                return $.trim (this.innerHTML);
            }).get()];
        }).get();
        
        var csv = "";
        csv = csv + headers.join (';') + "\n";
        $.each (rows, function (index, row) {
            csv = csv + row.join (';') + "\n";
        });
        
        $.ajax ({
            url: 'export-csv.php',
            type: 'POST',
            data: {
                csv: csv
            },
            success: function (result) {
                var blob=new Blob([result]);
                var link=document.createElement('a');
                link.href=window.URL.createObjectURL(blob);
                link.download="umutextstats.csv";
                link.click();
            }
        });
    });

    // Toggle
    table.find ('.toggle-cols-action').click (function () {
        
        // Get elements
        var self = $(this);
        var parent = self.closest ('th');
        var level = parent.attr ('data-level') * 1;
        var found_same_level = false;
        
        
        // Toggle
        self.toggleClass ('is-toggled');
        
        
        // Fetch childs
        var childs = parent.nextAll ().filter (function() {
        
            var current_level = $(this).attr("data-level") * 1;
            
            if (found_same_level) {
                return false;
            }
        
            if (current_level == level) {
                found_same_level = true;
                return false;
            }
        
        
            return current_level > level;
            
        });
        
        
        // Toggle
        childs.each (function () {
            var index = $(this).index () + 1;
            table.find ('tr:first-child th:nth-child(' + index + ')').toggle ( ! self.hasClass ('is-toggled'));
            table.find ('td:nth-child(' + index + ')').toggle ( ! self.hasClass ('is-toggled'));
        });
        
    });

});
