// Localsotage polyfill
// @link https://gist.github.com/juliocesar/926500
(function (isStorage) {
    if (!isStorage) {
        var data = {},
            undef;
        window.localStorage = {
            setItem     : function(id, val) { return data[id] = String(val); },
            getItem     : function(id) { return data.hasOwnProperty(id) ? data[id] : undef; },
            removeItem  : function(id) { return delete data[id]; },
            clear       : function() { return data = {}; }
        };
    }
})((function () {
    try {
        return "localStorage" in window && window.localStorage != null;
    } catch (e) {
        return false;
    }
})());


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
    var modal = $('.modal');
    var toggled_columns = new Array ();
    
    
    // Remember last toggled columns
    function toggle_columns () {
        
        // Toggle headers
        var style_css = '';
        var style = $('#toggled-columns');
        style.html ('');
        
        
        $.each (toggled_columns, function (index, column_key) {
            style_css += 'tr:first-child th[data-key-full^="' + column_key + '|"] { display: none; }';
        });
        
        
        // Toggle tds
        var indexes = [];
        style.html (style_css);
        style_css = '';
        
        table.find ('tr:first-child th:hidden').each (function () {
            indexes.push ($(this).index ());
        });
        
        $.each (indexes, function (index, row_index) {
            style_css += 'td:nth-child(' + row_index + ') {display:none;}';
        });
        
        
        style.append (style_css);
        
    }
    
    
    // Toggle filter
    $('.toggle-filter-action').click (function (e) {
        e.preventDefault ();
        e.stopPropagation ();
        body.toggleClass ('state-toggled');
    });
    
    
    // Dictionary
    $('[name="dictionary"]').change (function (e) {
        var self = $(this);
        var dictionary = self.find ('option:selected').attr ('value');
        configuration.prop ('disabled', dictionary != "");
    });
    
    
    // Update configuration
    $('.update-config-action').click (function (e) {
    
        // Remove previous style sheet
        $('#inline-style-sheet').remove ();
        
        
        // Get configuration
        var config_string = configuration.val ();
        
        
        // Store in local-storage
        localStorage.setItem ('config', config_string);
    
    
        // Perform AJAX Call
        $.ajax ({
            method: 'POST',
            url: 'process-header.php', 
            dataType: "html",
            data: {
                configuration: config_string
            },
            success: function (html) {
                table.replaceWith (html);
                table = $('table');
                $('#config').modal ('hide');
                toggle_columns ();
            }
        });
    });
    
    
    // If when we load the application we already have a
    // config, use the last one
    if (localStorage.getItem ('config')) {
        configuration.val (localStorage.getItem ('config'));
        $('.update-config-action').trigger ('click');
    }
    
    
    // Load configuration
    $('.load-config-action').change (function (e) {
    
        var reader = new FileReader();
        var input = $(this)[0];
        
        reader.onload = function (e) {
            configuration.val (e.target.result);
        }
        
        reader.readAsText (input.files[0]);
    
    });
    
    // Download configuration
    $('.store-config-action').click (function (e) {
    
        // Hide modal due to an issue with vex
        modal.hide ();
    
        // Request name
        vex.dialog.prompt ({
            message: 'Please, specify the file name',
            placeholder: 'custom-config.xml',
            callback: function (filename) {
            
                if ( ! filename) {
                    modal.show ();
                    return;
                }
                
                
                // Attach extension
                if (filename.indexOf ('.') == -1) {
                    filename = filename + '.xml';
                }
                
    
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
                        link.setAttribute ('download', filename);
                        link.dataset.downloadurl = ['text/plain', link.download, link.href].join(':');
                        link.draggable = true;
                        link.classList.add ('dragout');
                        link.click ();
                        
                    },
                    complete: function () {
                        modal.show ();
                    }
                });
            }
        });
    });
    

    
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
            data.max = form.find ('[name="twitter-max-results"]').val ();
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

    
    var fetch_data = function (separator) {
        
        // Get headers
        var headers = $("thead th span[data-toggle]", table).map (function () {
            return $.trim (this.innerHTML);
        }).get();
        
        
        // Get rows
        var rows = $("tbody > tr", table).map (function () { 
            return [$("td span", this).map (function () { 
                return $.trim (this.innerHTML);
            }).get()];
        }).get();

        
        
        // Create CSV
        var response = "";
        response = response + headers.join (separator) + "\n";
        $.each (rows, function (index, row) {
            response = response + row.join (separator) + "\n";
        });
        
        
        return response;
    }
    
    
    // Export to CSV
    $('.export-csv-action').click (function () {
    
        // Request name
        vex.dialog.prompt ({
            message: 'Please, specify the file name',
            value: 'umutextstats.csv',
            callback: function (filename) {
                if ( ! filename) {
                    return;
                }
                
                
                // Attach extension
                if (filename.indexOf ('.') == -1) {
                    filename = filename + '.csv';
                }
                
                
                // Get file
                csv = fetch_data (";");
                
                
                // Download file
                $.ajax ({
                    url: 'export.php',
                    type: 'POST',
                    data: {
                        content: content
                    },
                    success: function (result) {
                        var blob = new Blob([result]);
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = filename;
                        link.click ();
                    }
                });
            }
        });
    });
    
    
    // Export to WEKA
    $('.export-arff-action').click (function () {
    
        // Request name
        vex.dialog.prompt ({
            message: 'Please, specify the file name',
            value: 'umutextstats.arff',
            callback: function (filename) {
                if ( ! filename) {
                    return;
                }
                
                
                // Attach extension
                if (filename.indexOf ('.') == -1) {
                    filename = filename + '.arff';
                }
                
                
                // Get file
                content = fetch_data (",");
                content = '@DATA\n' + content;

                
                
                // Download file
                $.ajax ({
                    url: 'export.php',
                    type: 'POST',
                    data: {
                        content: content
                    },
                    success: function (result) {
                        var blob = new Blob([result]);
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = filename;
                        link.click ();
                    }
                });                
                
            }
        });
    
    });
    
    
    // Toggle
    $(document).on ('click', '.toggle-cols-action', {}, function (e) {
        
        // Get elements
        var self = $(this);
        var parent = self.closest ('th');
        var key = parent.attr ('data-key');
        var new_value = parent.attr ('data-toggled') == 'false' ? 'true' : 'false';
        
        
        // Remove
        parent.attr ('data-toggled', new_value);
        
        
        // Add
        toggled_columns = [];
        table.find ('tr:first-child').find ('th[data-toggled="true"]').each (function () {
            toggled_columns.push ($(this).attr ('data-key'));
        });
        
        
        
        // Store
        toggle_columns ();
        localStorage.setItem ('toggled-columns', toggled_columns);
        
    });
    
    
    toggle_columns ();

});
