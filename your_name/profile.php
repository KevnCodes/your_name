<?php
include 'db.php';
session_start();

// Get the ID of the profile being viewed
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$target_id = mysqli_real_escape_string($conn, $_GET['id']);
$current_user_id = $_SESSION['user_id'] ?? null;

// Fetch the target user's data
$sql = "SELECT * FROM users WHERE id = '$target_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    die("User profile not found.");
}

$user = mysqli_fetch_assoc($result);

// Variables for the target user's UI
$display_name = $user['display_name'];
$theme_color = !empty($user['theme_color']) ? $user['theme_color'] : "#006400"; 
$desktop_texture = !empty($user['bg_texture']) ? $user['bg_texture'] : "bg-texture-dots";
$user_number = $user['user_number'] ?? "USR-0001";
$mood = $user['mood'] ?? "Feeling Retro";
$bio = $user['bio'] ?? "Welcome to my digital space. Click the folders to explore my profile.";
$created_at = $user['created_at'] ?? date("Y-m-d");
$profile_picture = $user['profile_picture'] ?? null; 

// Get the name of the person visiting (for the guestbook)
$visitor_name = "Anonymous";
if ($current_user_id) {
    $v_sql = "SELECT display_name FROM users WHERE id = '$current_user_id'";
    $v_res = mysqli_query($conn, $v_sql);
    if($v_row = mysqli_fetch_assoc($v_res)) {
        $visitor_name = $v_row['display_name'];
    }
}

// Get target user's saved stickers
$stickers = [];
$st_sql = "SELECT * FROM user_stickers WHERE user_id = '$target_id'";
$st_result = mysqli_query($conn, $st_sql);

if ($st_result && mysqli_num_rows($st_result) > 0) {
    while($row = mysqli_fetch_assoc($st_result)) {
        $stickers[] = [
            'id' => $row['id'],
            'url' => $row['sticker_url'],
            'x' => $row['pos_x'],
            'y' => $row['pos_y'],
            'size' => $row['size']
        ];
    }
} else {
    // ARRAY OF DEFAULT STICKERS IF NONE SAVED 
    $stickers = [
        ['id' => 1, 'url' => 'assets/Stickers/headphones.png', 'x' => '15%', 'y' => '20%', 'size' => '140px'],
        ['id' => 2, 'url' => 'assets/Stickers/digicam.png', 'x' => '8%', 'y' => '40%', 'size' => '140px'],
        ['id' => 3, 'url' => 'assets/Stickers/notebook.png', 'x' => '15%', 'y' => '50%', 'size' => '190px'],
        ['id' => 4, 'url' => 'assets/Stickers/macbook.png', 'x' => '5%', 'y' => '75%', 'size' => '190px'],
        ['id' => 5, 'url' => 'assets/Stickers/ipad.png', 'x' => '75%', 'y' => '22%', 'size' => '140px'],
        ['id' => 6, 'url' => 'assets/Stickers/game.png', 'x' => '80%', 'y' => '45%', 'size' => '130px'],
        ['id' => 7, 'url' => 'assets/Stickers/stamp.png', 'x' => '88%', 'y' => '55%', 'size' => '75px'],
        ['id' => 8, 'url' => 'assets/Stickers/highligter.png', 'x' => '75%', 'y' => '65%', 'size' => '115px'],
        ['id' => 9, 'url' => 'assets/Stickers/yarn.png', 'x' => '80%', 'y' => '72%', 'size' => '75px'],
        ['id' => 10, 'url' => 'assets/Stickers/scissor.png', 'x' => '75%', 'y' => '78%', 'size' => '85px'],
        ['id' => 11, 'url' => 'assets/Stickers/marker.png', 'x' => '85%', 'y' => '80%', 'size' => '40px'],
        ['id' => 12, 'url' => 'assets/Stickers/pencil.png', 'x' => '90%', 'y' => '80%', 'size' => '40px']
    ];
}
$stickers_json = json_encode($stickers);

// Check Friendship Status
$friend_status = 'none';
$sql_f = "SELECT * FROM friendships 
          WHERE (user_id1='$current_user_id' AND user_id2='$target_id') 
          OR (user_id1='$target_id' AND user_id2='$current_user_id')";
$res_f = mysqli_query($conn, $sql_f);

if ($f = mysqli_fetch_assoc($res_f)) {
    if ($f['status'] == 'accepted') {
        $friend_status = 'friends';
    } else if ($f['user_id1'] == $current_user_id) {
        $friend_status = 'pending_sent';
    } else {
        $friend_status = 'pending_received';
    }
}

$is_friend = ($friend_status === 'friends');

// Placeholder data for Gallery and Journal
$photos = []; 
$photo_count = count($photos);
$journal_count = 0; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo strtoupper($display_name); ?> | Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        [x-cloak] { display: none !important; }
        .yn-badge { font-size: 0.6rem; color: white; padding: 2px 6px; font-weight: bold; border: 1px solid #000; }
        .cover-letter { display: inline-block; opacity: 0; transform: translateY(-200px) rotate(20deg); }
        .animate-letters .cover-letter { animation: dropIn 0.8s forwards; }
        @keyframes dropIn { to { opacity: 1; transform: translate(0, 0) rotate(0deg); } }
        #master-container { transition: transform 0.6s cubic-bezier(0.19,1,0.22,1); }
    </style>
</head>
<body class="yn-body w-screen h-screen overflow-hidden relative mono"
      :class="desktopTexture" 
      x-data="{ 
        windows: { aboutme: false, journal: false, gallery: false, guestbook: false },
        friendStatus: '<?php echo $friend_status; ?>',
        isFriend: <?php echo $is_friend ? 'true' : 'false'; ?>,
        themeColor: '<?php echo $theme_color; ?>',
        desktopTexture: '<?php echo $desktop_texture; ?>',
        stickers: <?php echo htmlspecialchars($stickers_json, ENT_QUOTES, 'UTF-8'); ?>,
        
        friendAction(action) {
            fetch('friend_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${action}&target_id=<?php echo $target_id; ?>`
            }).then(() => {
                if (action === 'add') {
                    this.friendStatus = 'pending_sent';
                } else if (action === 'accept') {
                    this.friendStatus = 'friends';
                    this.isFriend = true;
                }
            });
        }
      }">

    <div id="cover-screen" class="absolute inset-0 w-full h-full flex flex-col items-center justify-center z-[100] transition-opacity" :class="desktopTexture">
        <div id="cover-logo" class="font-serif text-[10vw] font-black lowercase tracking-tighter animate-letters" style="font-family: 'Georgia', serif;">
            <?php 
                $chars = str_split(strtolower($display_name));
                foreach($chars as $index => $char) {
                    $delay = $index * 0.1;
                    $displayChar = $char === ' ' ? '&nbsp;' : $char;
                    echo "<span class='cover-letter' style='animation-delay: {$delay}s'>{$displayChar}</span>";
                }
            ?>
        </div>
        <p class="font-mono text-xs mt-4 uppercase animate-pulse">Scroll up to reveal profile</p>
    </div>

    <div id="master-container" class="absolute inset-0 w-full h-full z-[200] bg-transparent" style="transform: translateY(100vh);">
        
        <div class="absolute inset-0 w-full h-full -z-10" :class="desktopTexture"></div>

        <header class="fixed top-0 left-0 right-0 flex items-center justify-between px-5"
                :style="'height: 50px; background:' + themeColor + '; border-bottom: 3px solid #000; z-index: 8000;'">
            
            <div class="flex items-center gap-3 text-white">
                <span class="font-[900] text-[1.25rem] tracking-tighter" style="font-family: 'Georgia', serif;">your name</span>
                <span class="opacity-60 text-[0.7rem] font-bold"><?php echo $user_number; ?></span>
            </div>

            <div class="announcement-bar flex-1 mx-8 border border-white/20 bg-black/30 h-[24px] overflow-hidden">
                <span class="marquee-text text-[#00ff00] text-[0.65rem] font-mono">
                    ★ Viewing <?php echo $display_name; ?>'s Space ★ mood: <?php echo htmlspecialchars($mood); ?> ★ <?php echo date("l, F j, Y"); ?> ★
                </span>
            </div>

            <div class="flex items-center gap-2">
                <?php if($current_user_id): ?>
                    <a href="dashboard.php" class="win31-btn text-[0.68rem] px-3 py-1 text-black no-underline">🏠 MY DASHBOARD</a>
                <?php else: ?>
                    <a href="index.php" class="win31-btn text-[0.68rem] px-3 py-1 text-black no-underline">LOGIN / REGISTER</a>
                <?php endif; ?>
            </div>
        </header>

        <svg id="lanyard-svg" class="fixed top-0 left-0 w-full h-full pointer-events-none z-[7000]">
            <path id="lace-line" fill="none" :stroke="themeColor" stroke-width="4" stroke-linecap="round" d="" />
        </svg>

        <main class="absolute inset-0 top-[50px]">
            
            <template x-for="sticker in stickers" :key="sticker.id">
                <div class="yn-sticker absolute pointer-events-none z-10" 
                     :style="`top: ${sticker.y}; left: ${sticker.x}; width: ${sticker.size};`">
                    <img :src="sticker.url" alt="sticker" class="w-full h-auto object-contain select-none drop-shadow-md">
                </div>
            </template>

            <div id="id-card" class="yn-id-card absolute bg-[#e0e0e0] border-2 border-black p-2.5 shadow-[4px_4px_0_#000] text-center cursor-grab transition-transform" 
                 style="right: 80px; top: 120px; z-index: 7001; width: 140px; transform-origin: top center;" 
                 @click="if(!window.isRealDrag) windows.aboutme = true">
                
                <div class="absolute -top-[22px] left-1/2 -translate-x-1/2 flex flex-col items-center" style="z-index: 2;">
                    <div class="w-4 h-6 bg-white/40 border-2 border-black rounded-t-md backdrop-blur-sm shadow-sm"></div>
                    <div class="w-8 h-4 bg-gradient-to-b from-gray-300 to-gray-500 border-2 border-black rounded-sm relative -mt-2 shadow-md">
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-2 h-2 rounded-full border border-black bg-gray-200 shadow-inner"></div>
                    </div>
                </div>
                <div class="w-5 h-1.5 rounded-full border-2 border-black mx-auto mb-3 bg-[#f4f4f4] shadow-inner mt-1"></div>
                
                <div class="w-full h-[74px] border-2 border-black flex items-center justify-center text-white text-[2.5rem] font-mono font-bold mb-2 shadow-inner relative overflow-hidden"
                     :style="'background:' + themeColor + ';'">
                    <?php if($profile_picture): ?>
                        <img src="<?php echo $profile_picture; ?>" class="absolute inset-0 w-full h-full object-cover">
                    <?php else: ?>
                        <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <p class="font-bold text-[0.8rem] uppercase leading-tight truncate"><?php echo $display_name; ?></p>
                <p class="text-[0.6rem] text-gray-500 mb-1 truncate">@<?php echo strtolower(str_replace(' ', '_', $display_name)); ?></p>
                <div class="mt-1 py-1 px-2 text-white text-[0.55rem] font-bold tracking-widest border-2 border-black shadow-sm" :style="'background:' + themeColor">
                    <?php echo $user_number; ?>
                </div>
            </div>

            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[min(85vw,1000px)] h-[min(75vh,650px)] z-[1000]">
                <div class="win31-window w-full h-full flex flex-col shadow-[8px_8px_0_rgba(0,0,0,0.15)]">
                    <div class="flex justify-between items-center px-2 py-1 text-[0.75rem] font-bold text-white border-b-2 border-black select-none" 
                         :style="'background:' + themeColor + '; font-family: \'Courier Prime\', monospace; cursor: default;'">
                        <span>Program Manager — <?php echo $display_name; ?>'s Profile</span>
                        <span class="opacity-80 text-[0.7rem]">[ - [] ]</span>
                    </div>
                    
                    <div class="flex-1 flex flex-col bg-white m-1 border-2 border-black overflow-hidden">
                        <div class="bg-black border-b-2 border-black py-1 overflow-hidden announcement-bar">
                            <span class="marquee-text text-[#00ff00] text-[0.65rem] font-mono">
                                 ★ Welcome to <?php echo $display_name; ?>'s Space ★ mood: <?php echo htmlspecialchars($mood); ?> ★ user <?php echo $user_number; ?> ★ <span x-text="isFriend ? 'You are friends ✓' : 'Send a friend request to interact!'"></span> ★
                            </span>
                        </div>

                        <div class="flex-1 flex overflow-hidden">
                            <div class="w-[110px] border-r-2 border-black bg-[#e8e8e8] py-5 flex flex-col gap-5 items-center overflow-y-auto">
                                <div class="folder-icon" @click="windows.aboutme = true"><div class="folder-shape"></div><span class="folder-label">ABOUT ME</span></div>
                                <div class="folder-icon" @click="windows.journal = false"><div class="folder-shape"></div><span class="folder-label">JOURNAL</span></div>
                                <div class="folder-icon" @click="windows.gallery = true" :class="!isFriend ? 'opacity-40' : ''" :title="!isFriend ? 'Friends Only' : ''">
                                    <div class="folder-shape"></div><span class="folder-label">GALLERY</span>
                                </div>
                                <div class="folder-icon" @click="windows.guestbook = true"><div class="folder-shape"></div><span class="folder-label">GUESTBOOK</span></div>
                            </div>

                            <div class="flex-1 overflow-y-auto p-5 bg-[#fafafa]">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="post-card col-span-2">
                                        <div class="post-title font-mono font-bold uppercase text-xl border-b-2 border-black pb-1 mb-2">HELLO, I'M <?php echo strtoupper($display_name); ?>.</div>
                                        <p class="text-[0.85rem] leading-relaxed"><?php echo htmlspecialchars($bio); ?></p>
                                    </div>
                                    <div class="widget-box">
                                        <div class="widget-title">CURRENT MOOD</div>
                                        <p class="text-[0.9rem] text-center py-1 font-bold">🎧 <?php echo htmlspecialchars($mood); ?></p>
                                    </div>
                                    
                                    <div class="widget-box">
                                        <div class="widget-title">CONNECTION STATUS</div>
                                        <div class="flex flex-col gap-1.5 items-center py-1">
                                            <template x-if="friendStatus === 'friends'">
                                                <span class="yn-badge bg-[#006400] px-3 py-1">✓ YOU ARE FRIENDS</span>
                                            </template>
                                            <template x-if="friendStatus === 'pending_sent'">
                                                <span class="yn-badge bg-[#B8860B] px-3 py-1">⏳ REQUEST SENT</span>
                                            </template>
                                            <template x-if="friendStatus === 'pending_received'">
                                                <div class="flex flex-col items-center gap-1 w-full">
                                                    <span class="yn-badge bg-[#8B0000] px-2 w-full text-center">⚠ THEY SENT A REQUEST</span>
                                                    <button class="win31-btn-primary text-[0.6rem] py-0.5 w-full" @click="friendAction('accept')">ACCEPT REQUEST</button>
                                                </div>
                                            </template>
                                            <template x-if="friendStatus === 'none'">
                                                <button class="win31-btn text-[0.7rem] flex items-center gap-1 font-bold" @click="friendAction('add')">
                                                    + ADD FRIEND
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="post-card col-span-2">
                                        <div class="post-title uppercase font-mono font-bold">EXPLORE <?php echo $display_name; ?>'S SPACE</div>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <button class="win31-btn text-[0.7rem]" @click="windows.aboutme = true">👤 About Me</button>
                                            <button class="win31-btn text-[0.7rem]" @click="windows.journal = true">📓 Journal</button>
                                            
                                            <template x-if="isFriend">
                                                <button class="win31-btn text-[0.7rem]" @click="windows.gallery = true">🖼 Gallery</button>
                                            </template>
                                            
                                            <button class="win31-btn text-[0.7rem]" @click="windows.guestbook = true">📌 Guestbook</button>
                                        </div>
                                        
                                        <template x-if="!isFriend">
                                            <p class="text-[0.65rem] text-gray-500 mt-2 font-bold uppercase tracking-tight">
                                                ⚠ Add <?php echo $display_name; ?> as a friend to access their Gallery.
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template x-if="windows.aboutme">
                <div class="win31-window absolute z-[6000] w-[480px] h-[580px] left-[150px] top-[100px]">
                    <div class="win31-titlebar" :style="'background:' + themeColor">
                        <span>ABOUT_<?php echo strtoupper($display_name); ?>.INI</span>
                        <button class="win31-close-btn" @click="windows.aboutme = false">X</button>
                    </div>
                    <div class="win31-content p-6 overflow-y-auto bg-white flex flex-col h-[calc(100%-32px)]">
                        <div class="flex gap-6 mb-6">
                            <div class="flex-shrink-0 relative flex items-center justify-center text-white font-bold border-[3px] border-black shadow-[6px_6px_0_#000] overflow-hidden"
                                 :style="'width: 90px; height: 90px; background:' + themeColor + '; font-size: 2.8rem;'">
                                <?php if($profile_picture): ?>
                                    <img src="<?php echo $profile_picture; ?>" class="absolute inset-0 w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="font-mono font-bold"><?php echo strtoupper(substr($display_name, 0, 1)); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h2 class="text-[2rem] font-mono font-bold leading-[1.1] mb-1 truncate text-black"><?php echo $display_name; ?></h2>
                                <div class="text-[0.7rem] text-gray-500 font-bold uppercase tracking-tight">@<?php echo strtolower(str_replace(' ', '_', $display_name)); ?> · <span :style="'color:' + themeColor"><?php echo $user_number; ?></span></div>
                                <div class="text-[0.65rem] text-gray-400 mt-1 italic">joined <?php echo date("F Y", strtotime($created_at)); ?></div>
                            </div>
                        </div>

                        <div class="widget-box mb-4">
                            <div class="widget-title">CURRENT MOOD</div>
                            <p class="text-[0.85rem] text-center py-1">🎧 <?php echo htmlspecialchars($mood); ?></p>
                        </div>

                        <div class="widget-box flex-1 mb-4 overflow-hidden flex flex-col">
                            <div class="widget-title">ABOUT ME</div>
                            <div class="p-2 flex-1 overflow-y-auto">
                                <div class="text-[0.8rem] leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($bio); ?></div>
                            </div>
                        </div>

                        <div class="widget-box mb-4">
                            <div class="widget-title">SYSTEM INFO</div>
                            <div class="text-[0.6rem] p-1 font-mono uppercase space-y-0.5">
                                <div>USER_ID: <?php echo $user_number; ?></div>
                                <div>THEME: <span x-text="themeColor.toUpperCase()"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="windows.journal">
                <div class="win31-window absolute z-[6000] w-[600px] h-[450px] left-[200px] top-[120px]">
                    <div class="win31-titlebar" :style="'background:' + themeColor">
                        <span><?php echo strtoupper($display_name); ?>_JOURNAL.EXE</span>
                        <button class="win31-close-btn" @click="windows.journal = false">X</button>
                    </div>
                    <div class="win31-content bg-[#c0c0c0] flex flex-col h-[calc(100%-32px)]">
                        <div class="flex items-center gap-2 p-1 border-b-2 border-black bg-[#c0c0c0]">
                            <span class="ml-auto text-[0.65rem] text-gray-600 mr-2 uppercase">C:/JOURNAL/ — <?php echo $journal_count; ?> files (READ ONLY)</span>
                        </div>
                        <div class="flex flex-1 overflow-hidden">
                            <div class="w-[120px] border-r-2 border-black bg-[#e8e8e8] p-2 text-[0.65rem]">
                                <div class="font-bold border-b border-black mb-2 pb-1">DRIVE C:</div>
                                <div>📁 JOURNAL</div>
                            </div>
                            <div class="flex-1 bg-white overflow-y-auto">
                                <div class="m-auto text-center p-10 text-gray-400 text-[0.8rem]">User has no public entries yet.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="windows.gallery && isFriend">
                <div class="win31-window absolute z-[6000] w-[700px] h-[500px] left-[250px] top-[140px]">
                    <div class="win31-titlebar" :style="'background:' + themeColor">
                        <span>PHOTO_GALLERY.DLL</span>
                        <button class="win31-close-btn" @click="windows.gallery = false">X</button>
                    </div>
                    
                    <div class="win31-content flex flex-col h-[calc(100%-32px)]">
                        <div class="gallery-toolbar flex justify-end">
                            <span class="text-[10px] text-gray-600 font-mono uppercase">
                                <?php echo $photo_count; ?> PHOTOS LOADED
                            </span>
                        </div>

                        <div class="gallery-container flex-1 overflow-hidden">
                            <div class="gallery-stage" id="mainGalleryStage">
                                <?php if(!empty($photos)): ?>
                                    <img src="<?php echo $photos[0]['file_path']; ?>" id="activeGalleryImg" alt="Gallery View">
                                <?php else: ?>
                                    <div class="text-[#444] text-[0.8rem] text-center p-10 font-mono">
                                        NO PHOTOS IN DIRECTORY.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="film-strip">
                                <?php foreach($photos as $index => $photo): ?>
                                    <div class="relative group">
                                        <img 
                                            src="<?php echo $photo['file_path']; ?>" 
                                            class="strip-item <?php echo $index === 0 ? 'active' : ''; ?>"
                                            onclick="switchPhoto(this, '<?php echo $photo['file_path']; ?>')"
                                        >
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="windows.gallery && !isFriend">
                <div class="win31-window absolute z-[6000] w-[300px] left-[350px] top-[200px]">
                    <div class="win31-titlebar bg-red-900 text-white">
                        <span>ACCESS DENIED</span>
                        <button class="win31-close-btn" @click="windows.gallery = false">X</button>
                    </div>
                    <div class="p-4 bg-[#c0c0c0] text-center border-2 border-t-white border-l-white border-b-gray-600 border-r-gray-600">
                        <div class="text-4xl mb-2">🔒</div>
                        <p class="text-[0.75rem] font-bold">Friends only.</p>
                        <p class="text-[0.65rem] text-gray-600 mb-3 mt-1">Add <?php echo $display_name; ?> to view their gallery.</p>
                        <button class="win31-btn w-full py-1" @click="windows.gallery = false">OK</button>
                    </div>
                </div>
            </template>

            <template x-if="windows.guestbook">
                <div class="win31-window absolute z-[6000] w-[650px] h-[520px] left-[200px] top-[120px]"
                     x-data="{ 
                        notes: [], 
                        message: '', 
                        selectedColor: '#ffff88', 
                        colors: ['#ffff88', '#ffccff', '#ccffff', '#ccffcc', '#ffd1dc', '#ffeaa7', '#dfe6e9'],
                        addNote() {
                            if(!this.message.trim()) return;
                            this.notes.push({
                                id: Date.now(),
                                author: '<?php echo addslashes($visitor_name); ?>',
                                text: this.message,
                                color: this.selectedColor,
                                x: (10 + Math.random() * 60) + '%',
                                y: (10 + Math.random() * 50) + '%'
                            });
                            this.message = '';
                        }
                     }">
                    <div class="win31-titlebar" :style="'background:' + themeColor">
                        <span>GUESTBOOK.EXE</span>
                        <button class="win31-close-btn" @click="windows.guestbook = false">X</button>
                    </div>
                    <div class="win31-content bg-[#e8e8e8] flex flex-col h-[calc(100%-32px)] overflow-hidden">
                        <div class="bulletin-board flex-1 relative bg-[#d2b48c] shadow-inner overflow-hidden" style="background-image: radial-gradient(#8b4513 1px, transparent 1px); background-size: 20px 20px;">
                            <template x-for="note in notes" :key="note.id">
                                <div class="sticky-note absolute p-3 shadow-md border border-black/10 transition-transform hover:scale-105"
                                     :style="`background: ${note.color}; left: ${note.x}; top: ${note.y}; width: 150px; z-index: 10; transform: rotate(${Math.random() * 6 - 3}deg);`"
                                     x-transition>
                                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-3 h-3 bg-red-600 rounded-full border border-black shadow-md"></div>
                                    <div class="text-[0.6rem] font-bold uppercase border-b border-black/10 mb-2 pb-1">FROM: <span x-text="note.author"></span></div>
                                    <div class="text-[0.75rem] leading-tight" x-text="note.text"></div>
                                </div>
                            </template>
                            <div x-show="notes.length === 0" class="absolute inset-0 flex items-center justify-center text-[#8a6a3a] text-sm opacity-50 italic">Leave a note on <?php echo $display_name; ?>'s board...</div>
                        </div>

                        <template x-if="isFriend">
                            <div class="p-3 bg-[#c0c0c0] border-t-2 border-black flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <textarea x-model="message" class="win31-textarea flex-1 h-16 p-2 text-[0.8rem]" placeholder="Write something to <?php echo $display_name; ?>..."></textarea>
                                    <button @click="addNote" class="win31-btn-primary px-4 font-bold flex flex-col items-center justify-center">PIN 📌</button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[0.6rem] font-bold">COLOR:</span>
                                    <div class="flex gap-1">
                                        <template x-for="c in colors">
                                            <div @click="selectedColor = c" class="w-5 h-5 border border-black cursor-pointer" :style="`background: ${c}; outline: ${selectedColor === c ? '2px solid black' : 'none'}; outline-offset: 1px;`"></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="!isFriend">
                            <div class="p-3 bg-[#c0c0c0] border-t-2 border-black text-center text-[0.7rem] font-bold text-gray-600 uppercase tracking-tight">
                                ⚠ Only friends can leave sticky notes.
                            </div>
                        </template>

                    </div>
                </div>
            </template>

        </main>
    </div>

    <script src="js/script.js"></script>
    <script>
       
        // PHYSICS FOR THE LANYARD AND ID CARD
        const card = document.getElementById("id-card");
        const lace = document.getElementById("lace-line");
        
        let isDraggingCard = false;
        window.isRealDrag = false; 

        // Initial targets (where the card rests)
        let targetX = window.innerWidth - 200; 
        let targetY = 140; 

        let cardX = targetX;
        let cardY = -200; // Starts off-screen so it drops in nicely!
        let vX = 0;
        let vY = 0;

        let dragOffsetX = 0;
        let dragOffsetY = 0;
        let startMouseX = 0;
        let startMouseY = 0;

        function physicsLoop() {
            if(window.innerWidth < 768) {
                requestAnimationFrame(physicsLoop);
                return; 
            }

            // Keep target updated in case window resizes
            targetX = window.innerWidth - 220; 

            if (!isDraggingCard) {
                // YOUR ORIGINAL SPRING MATH
                vY = (vY + (targetY - cardY) * 0.1) * 0.85; 
                vX = (vX + (targetX - cardX) * 0.1) * 0.85; 
                cardY += vY; 
                cardX += vX;
            }

            // 1. Apply coordinates to the card
            card.style.top = cardY + "px"; 
            card.style.left = cardX + "px";

            // 2. Read the EXACT rendered position to draw the lace perfectly centered
            const cX = card.offsetLeft + (card.offsetWidth/2) - 1;  // -1 to align with the lace's center
            const cY = card.offsetTop + 40;  // puts it right behind the clip's hole
            
            const originX = window.innerWidth - 150;

            // Draw a perfectly STRAIGHT line connecting to the EXACT card position
            lace.setAttribute('d', `M ${originX} 50 L ${cX} ${cY}`);

            requestAnimationFrame(physicsLoop);
        }

        if(card) {
            card.addEventListener('mousedown', (e) => {
                isDraggingCard = true;
                window.isRealDrag = false; 
                
                startMouseX = e.clientX;
                startMouseY = e.clientY;

                // Get exact click offset relative to the card's top-left corner
                dragOffsetX = e.clientX - card.offsetLeft; 
                dragOffsetY = e.clientY - card.offsetTop;

                card.classList.remove("transition-transform");
                
                const onMouseMove = (m) => {
                    // Instantly move the card with the mouse
                    cardX = m.clientX - dragOffsetX;
                    cardY = m.clientY - dragOffsetY;
                    
                    // Differentiate between a tap and a drag
                    if (Math.abs(m.clientX - startMouseX) > 5 || Math.abs(m.clientY - startMouseY) > 5) {
                        window.isRealDrag = true;
                    }
                };
                
                const onMouseUp = () => {
                    isDraggingCard = false;
                    setTimeout(() => { window.isRealDrag = false; }, 50);
                    window.removeEventListener('mousemove', onMouseMove);
                    window.removeEventListener('mouseup', onMouseUp);
                };
                
                window.addEventListener('mousemove', onMouseMove);
                window.addEventListener('mouseup', onMouseUp);
            });

            // Start the loop!
            physicsLoop();
        }

        const masterContainer = document.getElementById('master-container');
        const coverScreen = document.getElementById('cover-screen');
        const coverLogo = document.getElementById('cover-logo');
        
        // Starts False to show Cover Screen on Load
        let isDashboardActive = false; 
        
        window.addEventListener('wheel', (e) => {
            if(e.target.closest('.win31-content') || e.target.closest('.gallery-container') || e.target.closest('.yn-sticker')) return;

            if (e.deltaY < -20 && !isDashboardActive) {
                masterContainer.style.transform = 'translateY(0vh)';
                isDashboardActive = true;
                coverLogo.classList.remove('animate-letters');
            } 
            else if (e.deltaY > 20 && isDashboardActive) {
                masterContainer.style.transform = 'translateY(100vh)';
                isDashboardActive = false;
                
                coverLogo.classList.remove('animate-letters');
                void coverLogo.offsetWidth; 
                coverLogo.classList.add('animate-letters');
            }
        });
    </script>
</body>
</html>