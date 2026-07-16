<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/admin/file_management.php
Description: admin finder style file management
First Written on: Tuesday, 30-Jun-2026
Edited on: Saturday, 4-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

        // get folder + selected item from url
        if (isset($_GET['folder'])) {
            $folder = $_GET['folder'];
        } else {
            $folder = '';
        }

        if (isset($_GET['select'])) {
            $select = $_GET['select'];
        } else {
            $select = '';
        }


        // folder definitions
        $folders = array(
            'logs' => array(
                'label'  => 'System Logs',
                'path'   => 'uploads/logs',
                'delete' => true
            ),
            'reports' => array(
                'label'  => 'Reports',
                'path'   => 'uploads/reports',
                'delete' => true
            ),
            'course' => array(
                'label'  => 'Course Materials',
                'path'   => 'uploads/game/course/materials',
                'delete' => false
            )
        );


        // build items for current view
        $items = array();

        if ($folder == '') {

            // 3 home folders
            foreach ($folders as $folder_key => $folder_info) {
                $folder_path = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/' . $folder_info['path'];

                // count pdfs in this folder
                $count = 0;
                $folder_files = scandir($folder_path);
                foreach ($folder_files as $filename) {
                    if (substr(strtolower($filename), -4) == '.pdf') $count++;
                }

                $items[$folder_key] = array(
                    'type'  => 'folder',
                    'name'  => $folder_info['label'],
                    'count' => $count,
                    'date'  => date('j M Y, g:ia', filemtime($folder_path))
                );
            }

        } else {

            // inside a folder: list pdfs
            $folder_path  = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/' . $folders[$folder]['path'];
            $folder_files = scandir($folder_path);

            foreach ($folder_files as $filename) {
                if ($filename == '.' || $filename == '..') continue;
                if (substr(strtolower($filename), -4) != '.pdf') continue;

                $size_kb = round(filesize($folder_path . '/' . $filename) / 1024);

                $items[$filename] = array(
                    'type' => 'file',
                    'name' => $filename,
                    'url'  => '/Implose.gg-src/' . $folders[$folder]['path'] . '/' . $filename,
                    'size' => $size_kb . ' KB',
                    'date' => date('j M Y, g:ia', filemtime($folder_path . '/' . $filename))
                );
            }
        }


        // default select = first item
        if ($select == '') {
            foreach ($items as $key => $item) {
                $select = $key;
                break;
            }
        }

        $selected = $items[$select];


        // subtitle line for preview
        if ($selected['type'] == 'folder') {
            if ($selected['count'] == 1) {
                $subtitle = 'Folder - 1 item';
            } else {
                $subtitle = 'Folder - ' . $selected['count'] . ' items';
            }
        } else {
            $subtitle = 'PDF Document - ' . $selected['size'];
        }


        // open button link
        if ($selected['type'] == 'folder') {
            $open_url = '?folder=' . $select;
        } else {
            $back_url = '/Implose.gg-src/pages/admin/file_management.php?folder=' . $folder . '&select=' . urlencode($select);
            $pdf_path = $folders[$folder]['path'] . '/' . $select;
            $open_url = '/Implose.gg-src/pages/pdf_viewer.php?file=' . urlencode($pdf_path) . '&back=' . urlencode($back_url);
        }
    ?>

    <title>File Management - Implose.gg Admin</title>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_file_management.css">
</head>


<body class="admin-body">
    <?php
        $current_page = 'admin_filemanagement';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <!-- Top -->
        <div class="fm-top">
            <h1>File Management</h1>
            <p>Browse and manage system PDF files.</p>
        </div>

        <!-- Finder window -->
        <div class="fm-window">

            <!-- Toolbar -->
            <div class="fm-toolbar">
                <?php if ($folder == '') { ?>
                    <span class="fm-back fm-back-disabled">‹</span>
                <?php } else { ?>
                    <a class="fm-back" href="/Implose.gg-src/pages/admin/file_management.php">‹</a>
                <?php } ?>

                <span class="fm-title">
                    <?php
                        if ($folder == '') {
                            echo 'uploads';
                        } else {
                            echo $folders[$folder]['label'];
                        }
                    ?>
                </span>

                <span class="fm-toolbar-spacer"></span>
            </div>

            <!-- White card -->
            <div class="fm-card">
                <div class="fm-body">

                    <!-- Grid -->
                    <div class="fm-grid">
                        <?php foreach ($items as $key => $item) {

                            // link to select this item
                            if ($folder == '') {
                                $self_link = '?select=' . urlencode($key);
                            } else {
                                $self_link = '?folder=' . $folder . '&select=' . urlencode($key);
                            }

                            // active state
                            if ($key == $select) {
                                $active = 'fm-item-selected';
                            } else {
                                $active = '';
                            }

                            // icon + subtitle
                            if ($item['type'] == 'folder') {
                                $icon = 'folder.svg';

                                if ($item['count'] == 1) {
                                    $item_subtitle = '1 item';
                                } else {
                                    $item_subtitle = $item['count'] . ' items';
                                }

                            } else {
                                $icon     = 'file_pdf.svg';
                                $item_subtitle = $item['size'];
                            }
                        ?>
                            <a class="fm-item <?php echo $active; ?>" href="<?php echo $self_link; ?>">
                                <img class="fm-item-icon" src="/Implose.gg-src/assets/images/icons/<?php echo $icon; ?>" alt="">

                                <span class="fm-item-name">
                                    <?php echo $item['name']; ?>
                                </span>

                                <span class="fm-item-subtitle">
                                    <?php echo $item_subtitle; ?>
                                </span>
                            </a>
                        <?php } ?>
                    </div>

                    <!-- Preview pane -->
                    <aside class="fm-preview">

                        <!-- Top icon / iframe -->
                        <div class="fm-preview-top">
                            <?php if ($selected['type'] == 'folder') { ?>
                                <img class="fm-preview-icon" src="/Implose.gg-src/assets/images/icons/folder.svg" alt="">
                            <?php } else { ?>
                                <iframe class="fm-preview-frame" src="/Implose.gg-src/assets/lib/pdfjs/web/viewer.html?file=<?php echo urlencode($selected['url']); ?>#pagemode=none&zoom=page-fit"></iframe>
                            <?php } ?>
                        </div>


                        <!-- Info -->
                        <div class="fm-preview-info">
                            <h3>
                                <?php echo $selected['name']; ?>
                            </h3>

                            <p class="fm-preview-subtitle">
                                <?php echo $subtitle; ?>
                            </p>


                            <div class="fm-preview-heading">Information</div>

                            <?php if ($selected['type'] == 'folder') { ?>

                                <div class="fm-info-row">
                                    <span class="fm-info-label">Kind</span>
                                    <span class="fm-info-value">Folder</span>
                                </div>

                                <div class="fm-info-row">
                                    <span class="fm-info-label">Items</span>
                                    <span class="fm-info-value"><?php echo $selected['count']; ?></span>
                                </div>

                                <div class="fm-info-row">
                                    <span class="fm-info-label">Modified</span>
                                    <span class="fm-info-value"><?php echo $selected['date']; ?></span>
                                </div>

                            <?php } else { ?>

                                <div class="fm-info-row">
                                    <span class="fm-info-label">Kind</span>
                                    <span class="fm-info-value">PDF Document</span>
                                </div>

                                <div class="fm-info-row">
                                    <span class="fm-info-label">Size</span>
                                    <span class="fm-info-value"><?php echo $selected['size']; ?></span>
                                </div>

                                <div class="fm-info-row">
                                    <span class="fm-info-label">Modified</span>
                                    <span class="fm-info-value"><?php echo $selected['date']; ?></span>
                                </div>

                            <?php } ?>


                            <!-- Actions -->
                            <div class="fm-preview-actions">
                                <a class="fm-btn fm-btn-primary" href="<?php echo $open_url; ?>">Open</a>

                                <?php if ($selected['type'] == 'file' && $folders[$folder]['delete']) { ?>
                                    <a class="fm-btn fm-btn-danger" href="/Implose.gg-src/actions/admin/delete_file.php?folder=<?php echo $folder; ?>&name=<?php echo urlencode($select); ?>">Delete</a>
                                <?php } ?>
                            </div>
                        </div>

                    </aside>

                </div>
            </div>

            <!-- Breadcrumb -->
            <div class="fm-breadcrumb">
                <img src="/Implose.gg-src/assets/images/icons/folder.svg" alt="">
                <span>uploads</span>
                <?php if ($folder != '') { ?>
                    <span class="fm-breadcrumb-separator">›</span>
                    <img src="/Implose.gg-src/assets/images/icons/folder.svg" alt="">
                    <span><?php echo $folders[$folder]['label']; ?></span>
                <?php } ?>
            </div>

        </div>

    </div>

</body>
</html>

