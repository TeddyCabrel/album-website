<?php
include 'main.php';
// Get the directory size
function dirSize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size+=$file->getSize();
    }
    return $size;
}
// Retrieve all media uploaded on the current day
$stmt = $pdo->prepare('SELECT m.*, a.email FROM media m LEFT JOIN accounts a ON a.id = m.acc_id WHERE cast(m.uploaded_date as DATE) = cast(now() as DATE) ORDER BY m.uploaded_date DESC');
$stmt->execute();
$media = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the total number of media
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM media');
$stmt->execute();
$media_total = $stmt->fetchColumn();
// Media awaiting approval
$stmt = $pdo->prepare('SELECT m.*, a.email FROM media m LEFT JOIN accounts a ON a.id = m.acc_id WHERE m.approved = 0 ORDER BY m.uploaded_date DESC');
$stmt->execute();
$media_awaiting_approval = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Dashboard', 'dashboard')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-gauge-high"></i>
        <div class="txt">
            <h2>Dashboard</h2>
            <p>View statistics, recent media, and more.</p>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>Today's Media</h3>
            <p><?=number_format(count($media))?></p>
        </div>
        <i class="fa-solid fa-photo-film"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total media for today
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Awaiting Approval</h3>
            <p><?=number_format(count($media_awaiting_approval))?></p>
        </div>
        <i class="fas fa-clock"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Media awaiting approval
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Total Media</h3>
            <p><?=number_format($media_total)?></p>
        </div>
        <i class="fa-solid fa-folder-open"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total media
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Total Size</h3>
            <p><?=convert_filesize(dirSize('../media'))?></p>
        </div>
        <i class="fas fa-file-alt"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total media file size
        </div>
    </div>
</div>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-photo-film alt"></i>
        <div class="txt">
            <h2>Today's Media</h2>
            <p><?=number_format(count($media))?> new media uploads.</p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Media</td>
                    <td class="responsive-hidden">Description</td>
                    <td class="responsive-hidden">Account</td>
                    <td class="responsive-hidden">Type</td>
                    <td>Approved</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($media)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no recent media files</td>
                </tr>
                <?php else: ?>
                <?php foreach ($media as $m): ?>
                <tr>
                    <td>
                        <div class="media">
                            <a href="../<?=$m['filepath']?>" class="media-img" target="_blank" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>">
                            <?php if ($m['type'] == 'image'): ?>
                            <img src="../<?=$m['filepath']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php elseif ($m['type'] == 'video'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-film"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php elseif ($m['type'] == 'audio'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-music"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php endif; ?>
                            </a>
                            <a href="../<?=$m['filepath']?>" target="_blank" class="link1" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>"><?=htmlspecialchars($m['title'], ENT_QUOTES)?></a>
                        </div>
                    </td>
                    <td class="responsive-hidden"><div class="truncate"><?=htmlspecialchars($m['description'], ENT_QUOTES)?></div></td>
                    <td class="responsive-hidden"><?=$m['acc_id'] ? '<a class="link1" href="account.php?id=' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . '">' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . ' - ' . htmlspecialchars($m['email'], ENT_QUOTES) . '</a></td>' : '--'; ?></td>
                    <td class="responsive-hidden"><?=ucfirst($m['type'])?></td>
                    <td style="font-weight:500;color:<?=$m['approved']?'green':'red'?>"><?=$m['approved']?'Yes':'No'?></td>
                    <td class="responsive-hidden"><?=date('F j, Y H:ia', strtotime($m['uploaded_date']))?></td>
                    <td>
                        <a href="media.php?id=<?=$m['id']?>" class="link1">Edit</a>
                        <a href="allmedia.php?delete=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this media?')">Delete</a>
                        <?php if (!$m['approved']): ?>
                        <a href="allmedia.php?approve=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to approve this media?')">Approve</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-title" style="margin-top:40px">
    <div class="title">
        <i class="fa-solid fa-clock-rotate-left alt"></i>
        <div class="txt">
            <h2>Awaiting Approval</h2>
            <p><?=number_format(count($media_awaiting_approval))?> media awaiting approval.</p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Media</td>
                    <td class="responsive-hidden">Description</td>
                    <td class="responsive-hidden">Account</td>
                    <td class="responsive-hidden">Type</td>
                    <td>Approved</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($media_awaiting_approval)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no media files awaiting approval</td>
                </tr>
                <?php else: ?>
                <?php foreach ($media_awaiting_approval as $m): ?>
                <tr>
                    <td>
                        <div class="media">
                            <a href="../<?=$m['filepath']?>" class="media-img" target="_blank" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>">
                            <?php if ($m['type'] == 'image'): ?>
                            <img src="../<?=$m['filepath']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php elseif ($m['type'] == 'video'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-film"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php elseif ($m['type'] == 'audio'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-music"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php endif; ?>
                            </a>
                            <a href="../<?=$m['filepath']?>" target="_blank" class="link1" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>"><?=htmlspecialchars($m['title'], ENT_QUOTES)?></a>
                        </div>
                    </td>
                    <td class="responsive-hidden"><div class="truncate"><?=htmlspecialchars($m['description'], ENT_QUOTES)?></div></td>
                    <td class="responsive-hidden"><?=$m['acc_id'] ? '<a class="link1" href="account.php?id=' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . '">' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . ' - ' . htmlspecialchars($m['email'], ENT_QUOTES) . '</a></td>' : '--'; ?></td>
                    <td class="responsive-hidden"><?=ucfirst($m['type'])?></td>
                    <td style="font-weight:500;color:<?=$m['approved']?'green':'red'?>"><?=$m['approved']?'Yes':'No'?></td>
                    <td class="responsive-hidden"><?=date('F j, Y H:ia', strtotime($m['uploaded_date']))?></td>
                    <td>
                        <a href="media.php?id=<?=$m['id']?>" class="link1">Edit</a>
                        <a href="allmedia.php?delete=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this media?')">Delete</a>
                        <?php if (!$m['approved']): ?>
                        <a href="allmedia.php?approve=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to approve this media?')">Approve</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?=template_admin_footer()?>