<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>UMUTextStats GUI</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link href="favicon.ico" rel="Shortcut Icon">
        <link href="apple-touch-icon.png" rel="apple-touch-icon">
        
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        
        <link rel="stylesheet" href="css/vendor/vex.css">
        <link rel="stylesheet" href="css/vendor/vex-theme-plain.css">
        <link rel="stylesheet" href="css/main.css?v=<?= rand (1, 1000) ?>">
        
        <style id="toggled-columns"></style>

    </head>
    <body>
    
        <main>
            
            <div class="filter-wrapper">
            
                <!-- Title -->
                <h1>
                    <a href="/umutextstats-gui/">
                        UMUTextStats GUI
                    </a>
                </h1>
                
        
                <p>
                    This is a GUI tool to use the <em>UMUTextStats</em> tool. 
                </p>
                
                <form method="post" enctype="multipart/form-data">
                    
                    <!-- Hidden fields -->
                    <input type="hidden" name="form-action" value="submit">
                    
                    
                    <!-- Select dictionary -->
                    <div>
                        <label>
                            Dictionary
                            <a href="javascript:null" data-toggle="modal" data-target="#config">
                                <span class="fa fa-cog"></span>
                            </a>
                        </label>
                    </div>
                    
                    
                    <ul class="nav nav-tabs" role="tablist" id="tabs">
                        <li role="presentation" class="active twitter-tab-wrapper">
                            <a href="#twitter-tab" aria-controls="twitter-tab" role="tab" data-toggle="tab">
                                Twitter
                            </a>
                        </li>
                        <li role="presentation" class="text-tab-wrapper">
                            <a href="#text-tab" aria-controls="text-tab" role="tab" data-toggle="tab">
                                Text
                            </a>
                        </li>
                        <li role="presentation" class="file-tab-wrapper">
                            <a href="#file-tab" aria-controls="file-tab" role="tab" data-toggle="tab">
                                Files
                            </a>
                        </li>
                    </ul>
                    
                    
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="twitter-tab">
                            <div class="form-group">
                                <label for="query">
                                    Twitter query string
                                    <small>
                                        <a href="https://dev.twitter.com/rest/public/search" target="_blank">
                                            Tips
                                        </a>
                                     </small>
                                </label>
                                
                                <input 
                                    autofocus
                                    type="text" 
                                    class="form-control" 
                                    name="query" 
                                    placeholder="Cadena de búsqueda para tweets" 
                                    value="<?= htmlspecialchars ($_POST['query'] ?? '') ?>" 
                                />
                                
                                <input 
                                    style="margin: .75rem 0; max-width: 6rem;"
                                    class="form-control" 
                                    maxlength="6" 
                                    type="number" 
                                    name="twitter-max-results" 
                                    value="10" /> 

                                
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="text-tab">
                            <div class="form-group">
                                <label for="query">General purpose content</label>
                            
                                <textarea 
                                    rows="3"
                                    name="content" 
                                    class="form-control"
                                    placeholder="Cadena de texto"
                                ><?= htmlspecialchars ($_POST['content'] ?? '') ?></textarea>
                                    
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="file-tab">
                            <div class="form-group">
                                <label for="query">
                                    File
                                    <small>allows .txt. .zip</small>
                                </label>
                                <input 
                                    type="file" 
                                    class="form-control" 
                                    name="file" 
                                    placeholder="Fichero .txt o .rar" 
                                    accept=".txt,.csv,application/zip,application/rar"
                                />
                            </div>
                        </div>
                    </div>

                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            Send
                        </button><br /><br />
                        
                        <button type="button" class="btn btn-default export-csv-action">
                            Export to CSV
                            <span class="fa fa-download"></span>
                        </button>
                        
                        <button type="button" class="btn btn-default export-arff-action">
                            Export to ARFF
                            <span class="fa fa-download"></span>
                        </button>
                        
                    </div>
                </form>
            </div>
            
            <button type="button" class="filter-button toggle-filter-action">
                <span class="fa fa-bars"></span>
            </button>
            
            
            <!-- Right side -->
            <?php require ('api/process-header.php') ?>
            
        </main>
        
        
        <!-- Config -->
        <div class="modal fade" id="config" tabindex="-1" role="dialog" aria-labelledby="config">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Config
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    
                    <div class="modal-body">
                    
                        <!-- Select dictionary -->
                        <div class="row">
                            <div class="col-md-12">
                            
                                <div class="form-group">
                                    <label for="dictionary">
                                        Dictionary
                                        <a href="javascript:null" data-toggle="modal" data-target="#config">
                                            <span class="fa fa-cog"></span>
                                        </a>
                                    </label>
                                    
                                    <select name="dictionary" class="form-control">
                                    
                                        <option value="">
                                            Custom
                                        </option>
                                    
                                        <?php foreach (array ('spanish' => 'Spanish', 'english' => 'English') as $key => $language) : ?>
                                            <option 
                                                data-url="https://github.com/Smolky/TextAnalysisTool/tree/master/assets/dictionaries/<?= $key ?>" 
                                                <?= ($_POST['dictionary'] ?? '') == $key ? 'selected' : '' ?> 
                                                value="<?= $key ?>">
                                                <?= $language ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                            
                                <textarea 
                                    spellcheck="false"
                                    autocapitalize="off"
                                    autocorrect="off"
                                    rows="10"
                                    name="configuration" 
                                    class="form-control"><?= $raw_config ?></textarea>
                                <br />
                                
                                <div style="text-align: right;">
                                    
                                    <button type="button" class="btn btn-primary update-config-action">
                                        Update
                                    </button>
                                    
                                    <button type="button" class="btn btn-default store-config-action">
                                        Save
                                    </button>
                                    
                                    <label class="btn btn-default ">
                                        <input 
                                            class="hide load-config-action" 
                                            type="file"
                                            accept=".xml"
                                        />
                                        Load
                                    </label>
                                    
                                </div>

                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        
        <!-- Javascripts -->
        <script src="js/vendor/jquery-1.12.0.min.js"></script>
        <script src="js/vendor/vex.combined.min.js"></script>
        <script>vex.defaultOptions.className = 'vex-theme-plain'</script>
        
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <script src="js/plugins.js"></script>
        <script src="js/main.js?v=<?= rand (1, 1000) ?>"></script>

    </body>
</html>