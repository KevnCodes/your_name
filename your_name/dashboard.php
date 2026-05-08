<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// FOR SAVING STICKERS
if (isset($_POST['action']) && $_POST['action'] === 'save_stickers' && isset($_POST['stickers'])) {
    $stickers_data = json_decode($_POST['stickers'], true);
    mysqli_query($conn, "DELETE FROM user_stickers WHERE user_id = '$user_id'");
    if(is_array($stickers_data)) {
        foreach($stickers_data as $st) {
            $url = mysqli_real_escape_string($conn, $st['url'] ?? '');
            $x = mysqli_real_escape_string($conn, $st['x'] ?? '');
            $y = mysqli_real_escape_string($conn, $st['y'] ?? '');
            $size = mysqli_real_escape_string($conn, $st['size'] ?? '');
            mysqli_query($conn, "INSERT INTO user_stickers (user_id, sticker_url, pos_x, pos_y, size) VALUES ('$user_id', '$url', '$x', '$y', '$size')");
        }
    }
    exit('saved');
}

// FOR SAVING THEME/TEXTURE
if (isset($_POST['action']) && $_POST['action'] === 'save_theme') {
    $color = mysqli_real_escape_string($conn, $_POST['theme_color']);
    $texture = mysqli_real_escape_string($conn, $_POST['bg_texture']);
    mysqli_query($conn, "UPDATE users SET theme_color = '$color', bg_texture = '$texture' WHERE id = '$user_id'");
    exit('saved');
}

// Latest data from the database
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// Variables for the UI 
$display_name = $user['display_name'];
$theme_color = !empty($user['theme_color']) ? $user['theme_color'] : "#006400"; 
$desktop_texture = !empty($user['bg_texture']) ? $user['bg_texture'] : "bg-texture-dots";
$user_number = $user['user_number'] ?? "USR-0001";
$mood = $user['mood'] ?? "Feeling Retro";
$bio = $user['bio'] ?? "Your personal space on the internet. Click the folders on the left to explore your dashboard — journal entries, gallery, guestbook, and more.";
$created_at = $user['created_at'] ?? date("Y-m-d");
$profile_picture = $user['profile_picture'] ?? null; 

// Get user's saved stickers
$stickers = [];
$st_sql = "SELECT * FROM user_stickers WHERE user_id = '$user_id'";
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
    // Default stickers if table is empty for this user
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

// Placeholder data for the Gallery
$photos = []; 
$photo_count = count($photos);
$journal_count = 0; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo strtoupper($display_name); ?> | Dashboard</title>
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
      x-init="initFriends();"
      x-data="{ 
        windows: { aboutme: false, journal: false, gallery: false, guestbook: false, friends: false, customize: false },
        journalView: 'list', 
        themeColor: '<?php echo $theme_color; ?>',
        desktopTexture: '<?php echo $desktop_texture; ?>',
        
        /* --- STICKER SYSTEM --- */
        stickerEditMode: false, showStickerPrompt: false, pendingStickerUrl: null, stickerSizePx: 100,
        stickers: <?php echo htmlspecialchars($stickers_json, ENT_QUOTES, 'UTF-8'); ?>,
        availableStickerIcons: [ 'https://api.iconify.design/fluent-emoji:star.svg', 'https://api.iconify.design/fluent-emoji:alien-monster.svg' ],
        addRandomSticker() {
            const randomIcon = this.availableStickerIcons[Math.floor(Math.random() * this.availableStickerIcons.length)];
            this.stickers.push({ id: Date.now(), url: randomIcon, x: (20 + Math.random() * 50) + '%', y: (20 + Math.random() * 50) + '%', size: '80px' });
        },
        uploadCustomSticker(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => { this.pendingStickerUrl = e.target.result; this.showStickerPrompt = true; };
            reader.readAsDataURL(file);
            event.target.value = '';
        },
        stickIt() {
            if(!this.pendingStickerUrl) return;
            this.stickers.push({ id: Date.now(), url: this.pendingStickerUrl, x: '50%', y: '50%', size: this.stickerSizePx + 'px' });
            this.showStickerPrompt = false; this.pendingStickerUrl = null;
        },
        removeSticker(id) { this.stickers = this.stickers.filter(s => s.id !== id); },
        saveStickers() {
            let formData = new URLSearchParams();
            formData.append('action', 'save_stickers');
            formData.append('stickers', JSON.stringify(Alpine.raw(this.stickers) || this.stickers));
            fetch('dashboard.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData.toString() });
        },

        /* --- FRIENDS SYSTEM LOGIC --- */
        friendsTab: 'friends', friendsList: [], pendingList: [], searchQuery: '', searchResults: [], confirmRemoveFriend: null,
        initFriends() { this.fetchFriends(); this.searchUsers(); setInterval(() => { this.fetchFriends() }, 15000); },
        fetchFriends() { fetch('get_friends_data.php').then(res => res.json()).then(data => { this.friendsList = data.friends || []; this.pendingList = data.pending || []; }); },
        searchUsers() { fetch('search_users.php?q=' + encodeURIComponent(this.searchQuery)).then(res => res.json()).then(data => { this.searchResults = data || []; }); },
        friendAction(action, targetId) {
            fetch('friend_actions.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=${action}&target_id=${targetId}` })
            .then(() => { this.fetchFriends(); if(this.friendsTab === 'find') this.searchUsers(); this.confirmRemoveFriend = null; });
        }
      }">

    <input type="file" id="customStickerUpload" class="hidden" accept="image/*" @change="uploadCustomSticker($event)">

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
        <p class="font-mono text-xs mt-4 uppercase animate-pulse">Scroll up to reveal dashboard</p>
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
                    ★ Welcome back, <?php echo $display_name; ?>! ★ Your personal space is live. ★ <?php echo date("l, F j, Y"); ?> ★ Have a great day! ★
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button class="win31-btn text-[0.68rem] px-3 py-1" :class="stickerEditMode ? 'bg-[#ffff88]' : ''" @click="stickerEditMode = !stickerEditMode">✨ STICKERS</button>
                <button class="win31-btn text-[0.68rem] px-3 py-1" @click="windows.friends = true" x-text="`FRIENDS (${friendsList.length})`"></button>
                <button class="win31-btn p-1" @click="windows.customize = true" title="Settings"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></button>
                <a href="logout.php" class="win31-btn p-1 bg-red-800 text-white" title="Logout"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></a>
            </div>
        </header>

        <svg id="lanyard-svg" class="fixed top-0 left-0 w-full h-full pointer-events-none z-[7000]">
            <path id="lace-line" fill="none" :stroke="themeColor" stroke-width="4" stroke-linecap="round" d="" />
        </svg>

        <main class="absolute inset-0 top-[50px]">
            
            <template x-for="sticker in stickers" :key="sticker.id">
                <div class="yn-sticker absolute" 
                     :class="stickerEditMode ? 'is-editing cursor-grab pointer-events-auto z-[8000]' : 'pointer-events-none z-10'"
                     :style="`top: ${sticker.y}; left: ${sticker.x}; width: ${sticker.size};`"
                     @mousedown="if(stickerEditMode) { 
                        let s = $el; let startX = $event.clientX; let startY = $event.clientY; 
                        let startLeft = s.offsetLeft; let startTop = s.offsetTop;
                        let onMove = (e) => { s.style.left = (startLeft + e.clientX - startX) + 'px'; s.style.top = (startTop + e.clientY - startY) + 'px'; sticker.x = s.style.left; sticker.y = s.style.top; };
                        let onUp = () => { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); };
                        document.addEventListener('mousemove', onMove); document.addEventListener('mouseup', onUp); 
                     }">
                    <button x-show="stickerEditMode" @click.stop="removeSticker(sticker.id)"
                            class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 text-white rounded-full border-2 border-black flex items-center justify-center font-bold text-xs shadow-md z-[10000] hover:scale-110 transition-transform pointer-events-auto">X</button>
                    <img :src="sticker.url" alt="sticker" class="w-full h-auto object-contain select-none drop-shadow-md">
                </div>
            </template>

            <div x-show="stickerEditMode" x-cloak class="fixed bottom-4 left-1/2 -translate-x-1/2 z-[9000] bg-[#c0c0c0] border-2 border-black shadow-[4px_4px_0_#000] p-4 flex flex-col items-center gap-3 w-[400px]">
                <div class="text-[0.65rem] font-bold text-center uppercase tracking-wider">STICKER MODE - drag to move, × to remove</div>
                <div class="flex flex-col gap-2 w-full items-center">
                    <div class="flex gap-2 w-full">
                        <button @click="addRandomSticker()" class="win31-btn-primary flex-1 py-2 text-[0.7rem] whitespace-nowrap">+ RANDOM</button>
                        <button onclick="document.getElementById('customStickerUpload').click()" class="win31-btn-primary flex-1 py-2 text-[0.7rem] whitespace-nowrap">📤 UPLOAD .PNG</button>
                    </div>
                    <button @click="stickerEditMode = false; saveStickers();" class="win31-btn w-32 py-1 text-[0.7rem]">DONE</button>
                </div>
            </div>

            <div x-show="showStickerPrompt" class="fixed inset-0 bg-black/40 z-[9500] flex items-center justify-center" x-cloak>
                <div class="win31-window w-64 border-2 border-black shadow-[4px_4px_0_#000]">
                    <div class="win31-titlebar" :style="'background:' + themeColor"><span>STICK_IT.EXE</span></div>
                    <div class="p-4 bg-[#c0c0c0] flex flex-col gap-3 text-center">
                        <p class="text-[0.75rem] font-bold">Set sticker size in pixels (px):</p>
                        <input type="number" x-model="stickerSizePx" class="win31-input text-center text-lg font-bold p-1">
                        <div class="flex gap-2 mt-2">
                            <button class="win31-btn-primary flex-1 py-2" @click="stickIt()">Stick It!</button>
                            <button class="win31-btn flex-1 py-2" @click="showStickerPrompt = false; pendingStickerUrl = null; document.getElementById('customStickerUpload').value = '';">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

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
                        <span>Program Manager — <?php echo $display_name; ?>'s Desktop</span>
                        <span class="opacity-80 text-[0.7rem]">[ - [] ]</span>
                    </div>
                    
                    <div class="flex-1 flex flex-col bg-white m-1 border-2 border-black overflow-hidden">
                        <div class="bg-black border-b-2 border-black py-1 overflow-hidden announcement-bar">
                            <span class="marquee-text text-[#00ff00] text-[0.65rem] font-mono">
                                 ★ <?php echo $display_name; ?>'s Personal Space ★ mood: <?php echo htmlspecialchars($mood); ?> ★ user <?php echo $user_number; ?> ★ Welcome! ★
                            </span>
                        </div>

                        <div class="flex-1 flex overflow-hidden">
                            <div class="w-[110px] border-r-2 border-black bg-[#e8e8e8] py-5 flex flex-col gap-5 items-center overflow-y-auto">
                                <div class="folder-icon" @click="windows.aboutme = true"><div class="folder-shape"></div><span class="folder-label">ABOUT ME</span></div>
                                <div class="folder-icon" @click="windows.journal = true"><div class="folder-shape"></div><span class="folder-label">MY JOURNAL</span></div>
                                <div class="folder-icon" @click="windows.gallery = true"><div class="folder-shape"></div><span class="folder-label">GALLERY</span></div>
                                <div class="folder-icon" @click="windows.guestbook = true"><div class="folder-shape"></div><span class="folder-label">GUESTBOOK</span></div>
                            </div>

                            <div class="flex-1 overflow-y-auto p-5 bg-[#fafafa]">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="post-card col-span-2">
                                        <div class="post-title font-mono font-bold uppercase text-xl border-b-2 border-black pb-1 mb-2">WELCOME, <?php echo strtoupper($display_name); ?>.</div>
                                        <p class="text-[0.85rem] leading-relaxed"><?php echo htmlspecialchars($bio); ?></p>
                                    </div>
                                    <div class="widget-box">
                                        <div class="widget-title">CURRENT MOOD</div>
                                        <p class="text-[0.9rem] text-center py-1 font-bold">🎧 <?php echo htmlspecialchars($mood); ?></p>
                                    </div>
                                    <div class="widget-box">
                                        <div class="widget-title">SYSTEM INFO</div>
                                        <div class="text-[0.7rem] space-y-1 font-mono">
                                            <div>ID: <?php echo $user_number; ?></div>
                                            <div>COLOR: <span x-text="themeColor.toUpperCase()"></span></div>
                                            <div>STATUS: ONLINE</div>
                                        </div>
                                    </div>
                                    <div class="post-card col-span-2">
                                        <div class="post-title uppercase font-mono font-bold">Quick Access</div>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <button class="win31-btn text-[0.7rem]" @click="windows.journal = true">📝 New Journal Entry</button>
                                            <button class="win31-btn text-[0.7rem]" @click="windows.gallery = true">🖼️ Open Gallery</button>
                                            <button class="win31-btn text-[0.7rem]" @click="windows.guestbook = true">📌 View Messages</button>
                                            <button class="win31-btn text-[0.7rem]" @click="windows.customize = true">🎨 Customize</button>
                                            <button class="win31-btn text-[0.7rem]" @click="windows.friends = true">👥 Find Friends</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template x-if="windows.friends">
                <div class="win31-window absolute z-[6000] w-[450px] h-[550px] left-[200px] top-[100px]">
                    <div class="win31-titlebar" :style="'background:' + themeColor">
                        <span>FRIENDS.EXE</span>
                        <button class="win31-close-btn" @click="windows.friends = false">X</button>
                    </div>
                    
                    <div class="win31-content bg-[#c0c0c0] flex flex-col h-[calc(100%-32px)]">
                        <div class="flex gap-1 p-1 border-b-2 border-black bg-[#c0c0c0]">
                            <button :class="friendsTab === 'friends' ? 'win31-btn-primary' : 'win31-btn'" class="text-[0.65rem] px-3 py-1 relative" @click="friendsTab = 'friends'" x-text="`FRIENDS (${friendsList.length})`"></button>
                            <button :class="friendsTab === 'pending' ? 'win31-btn-primary' : 'win31-btn'" class="text-[0.65rem] px-3 py-1 relative" @click="friendsTab = 'pending'">
                                PENDING <span x-show="pendingList.length > 0" x-text="`(${pendingList.length})`"></span>
                                <span x-show="pendingList.length > 0" class="absolute -top-1 -right-1 w-2 h-2 bg-red-600 rounded-full border border-black"></span>
                            </button>
                            <button :class="friendsTab === 'find' ? 'win31-btn-primary' : 'win31-btn'" class="text-[0.65rem] px-3 py-1" @click="friendsTab = 'find'">FIND</button>
                        </div>

                        <div class="flex-1 bg-white overflow-y-auto relative">
                            <div x-show="friendsTab === 'friends'" class="h-full">
                                <div x-show="friendsList.length === 0" class="p-6 text-center text-[0.75rem] text-gray-500">No friends yet. Use FIND to discover people!</div>
                                <template x-for="f in friendsList" :key="f.id">
                                    <div class="flex items-center gap-3 p-2 border-b border-black/20 hover:bg-gray-50">
                                        <div class="w-10 h-10 border-2 border-black flex items-center justify-center text-white font-mono font-bold text-lg relative overflow-hidden" 
                                             :style="`background: ${f.theme_color};`">
                                            <template x-if="f.profile_picture">
                                                <img :src="f.profile_picture" class="absolute inset-0 w-full h-full object-cover">
                                            </template>
                                            <template x-if="!f.profile_picture">
                                                <span x-text="f.display_name.charAt(0).toUpperCase()"></span>
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-[0.8rem] truncate" x-text="f.display_name"></div>
                                            <div class="text-[0.6rem] text-gray-500" x-text="f.user_number"></div>
                                        </div>
                                        <div class="flex gap-2 items-center">
                                            <a :href="`profile.php?id=${f.id}`" class="win31-btn text-[0.6rem] px-2 py-1 uppercase no-underline text-black flex items-center">VIEW</a>
                                            <button class="text-red-700 font-bold px-2 hover:bg-red-100 border border-transparent hover:border-red-300" 
                                                    title="Remove friend" @click="confirmRemoveFriend = f.id">X</button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div x-show="friendsTab === 'pending'" class="h-full">
                                <div x-show="pendingList.length === 0" class="p-6 text-center text-[0.75rem] text-gray-500">No pending friend requests.</div>
                                <template x-for="p in pendingList" :key="p.id">
                                    <div class="flex items-center gap-3 p-2 border-b border-black/20 bg-[#ffffee]">
                                        <div class="w-10 h-10 border-2 border-black flex items-center justify-center text-white font-mono font-bold text-lg relative overflow-hidden" 
                                             :style="`background: ${p.theme_color};`">
                                            <template x-if="p.profile_picture">
                                                <img :src="p.profile_picture" class="absolute inset-0 w-full h-full object-cover">
                                            </template>
                                            <template x-if="!p.profile_picture">
                                                <span x-text="p.display_name.charAt(0).toUpperCase()"></span>
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-[0.8rem] truncate" x-text="p.display_name"></div>
                                            <div class="text-[0.6rem] text-gray-500" x-text="`@${p.display_name.toLowerCase().replace(' ', '_')} wants to connect`"></div>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <button class="win31-btn-primary text-[0.6rem] px-2 py-1" @click="friendAction('accept', p.id)">✓ ACCEPT</button>
                                            <div class="flex gap-1">
                                                <a :href="`profile.php?id=${p.id}`" class="win31-btn text-[0.6rem] px-2 py-1 text-black text-center flex-1 no-underline">VIEW</a>
                                                <button class="win31-btn text-[0.6rem] px-2 py-1 text-red-700 flex-1" @click="friendAction('reject', p.id)">X</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div x-show="friendsTab === 'find'" class="h-full">
                                <div class="p-2 border-b-2 border-black bg-[#f0f0f0]">
                                    <input type="text" class="win31-input" placeholder="Search by name..." 
                                           x-model="searchQuery" @input.debounce.500ms="searchUsers()">
                                </div>
                                <div x-show="searchResults.length === 0" class="p-6 text-center text-[0.75rem] text-gray-500">No users found.</div>
                                
                                <template x-for="u in searchResults" :key="u.id">
                                    <div class="flex items-center gap-3 p-2 border-b border-black/20 hover:bg-gray-50">
                                        <div class="w-10 h-10 border-2 border-black flex items-center justify-center text-white font-mono font-bold text-lg relative overflow-hidden" 
                                             :style="`background: ${u.theme_color};`">
                                            <template x-if="u.profile_picture">
                                                <img :src="u.profile_picture" class="absolute inset-0 w-full h-full object-cover">
                                            </template>
                                            <template x-if="!u.profile_picture">
                                                <span x-text="u.display_name.charAt(0).toUpperCase()"></span>
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-[0.8rem] truncate" x-text="u.display_name"></div>
                                            <div class="text-[0.6rem] text-gray-500" x-text="u.user_number"></div>
                                        </div>
                                        <div class="flex gap-2 items-center">
                                            <a :href="`profile.php?id=${u.id}`" class="win31-btn text-[0.6rem] px-2 py-1 no-underline text-black">VIEW</a>
                                            <span x-show="u.friend_status === 'friends'" class="yn-badge bg-[#006400]">FRIENDS</span>
                                            <span x-show="u.friend_status === 'pending_sent'" class="yn-badge bg-[#888]">PENDING</span>
                                            <button x-show="u.friend_status === 'none'" class="win31-btn text-[0.6rem] px-2 py-1" @click="friendAction('add', u.id)">+ ADD</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div x-show="confirmRemoveFriend !== null" class="absolute inset-0 bg-black/40 z-[100] flex items-center justify-center" x-cloak>
                        <div class="win31-window w-64 border-2 border-black shadow-[4px_4px_0_#000]">
                            <div class="win31-titlebar bg-[#8B0000] text-white"><span>REMOVE FRIEND?</span></div>
                            <div class="p-4 bg-[#c0c0c0] flex flex-col gap-3">
                                <p class="text-[0.75rem] font-bold">Remove this person from your friends?</p>
                                <div class="flex gap-2">
                                    <button class="win31-btn-primary flex-1 bg-[#8B0000]" @click="friendAction('remove', confirmRemoveFriend)">YES</button>
                                    <button class="win31-btn flex-1" @click="confirmRemoveFriend = null">NO</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="windows.aboutme">
                <div class="win31-window absolute z-[6000] w-[480px] h-[580px] left-[150px] top-[100px]"
                     x-data="{ 
                        editing: false,
                        form: { display_name: '<?php echo addslashes($display_name); ?>', mood: '<?php echo addslashes($mood); ?>', bio: `<?php echo addslashes($bio); ?>`, pfpPreview: null },
                        handlePfp(e) { 
                            let file = e.target.files[0];
                            if(!file) return;
                            
                            let reader = new FileReader();
                            reader.onload = (event) => { 
                                let img = new Image();
                                img.onload = () => {
                                    let canvas = document.createElement('canvas');
                                    let MAX_SIZE = 200;
                                    let width = img.width;
                                    let height = img.height;

                                    if (width > height) {
                                        if (width > MAX_SIZE) {
                                            height *= MAX_SIZE / width;
                                            width = MAX_SIZE;
                                        }
                                    } else {
                                        if (height > MAX_SIZE) {
                                            width *= MAX_SIZE / height;
                                            height = MAX_SIZE;
                                        }
                                    }
                                    
                                    canvas.width = width;
                                    canvas.height = height;
                                    let ctx = canvas.getContext('2d');
                                    ctx.drawImage(img, 0, 0, width, height);
                                    
                                    this.form.pfpPreview = canvas.toDataURL('image/jpeg', 0.8);
                                };
                                img.src = event.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                     }">
                    <div class="win31-titlebar" :style="'background:' + themeColor">
                        <span>ABOUT_<?php echo strtoupper($display_name); ?>.INI</span>
                        <button class="win31-close-btn" @click="windows.aboutme = false">X</button>
                    </div>
                    <div class="win31-content p-6 overflow-y-auto bg-white flex flex-col h-[calc(100%-32px)]">
                        <div class="flex gap-6 mb-6">
                            
                            <div class="flex-shrink-0 relative flex items-center justify-center text-white font-bold border-[3px] border-black shadow-[6px_6px_0_#000] overflow-hidden"
                                 :style="'width: 90px; height: 90px; background:' + themeColor + '; font-size: 2.8rem; cursor:' + (editing ? 'pointer' : 'default')"
                                 @click="if(editing) document.getElementById('pfpUpload').click()">
                                
                                <?php if($profile_picture): ?>
                                    <img src="<?php echo $profile_picture; ?>" class="absolute inset-0 w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="font-mono font-bold" x-show="!form.pfpPreview" x-text="form.display_name.charAt(0).toUpperCase()"></span>
                                <?php endif; ?>
                                
                                <img x-show="form.pfpPreview" :src="form.pfpPreview" class="absolute inset-0 w-full h-full object-cover z-10" x-cloak>
                                <div x-show="editing" class="absolute bottom-0 w-full bg-black/60 text-white text-[9px] font-mono text-center py-1 z-20 pointer-events-none">UPLOAD</div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <template x-if="!editing">
                                    <h2 class="text-[2rem] font-mono font-bold leading-[1.1] mb-1 truncate text-black" x-text="form.display_name"></h2>
                                </template>
                                <template x-if="editing">
                                    <input type="text" x-model="form.display_name" class="win31-input w-full text-[1.2rem] font-bold p-1 mb-1 border-2 border-black">
                                </template>
                                <div class="text-[0.7rem] text-gray-500 font-bold uppercase tracking-tight">@<?php echo strtolower(str_replace(' ', '_', $display_name)); ?> · <span :style="'color:' + themeColor"><?php echo $user_number; ?></span></div>
                                <div class="text-[0.65rem] text-gray-400 mt-1 italic">joined <?php echo date("F Y", strtotime($created_at)); ?></div>
                            </div>
                        </div>

                        <div class="widget-box mb-4">
                            <div class="widget-title">CURRENT MOOD</div>
                            <template x-if="!editing"><p class="text-[0.85rem] text-center py-1">🎧 <span x-text="form.mood"></span></p></template>
                            <template x-if="editing"><input type="text" x-model="form.mood" class="win31-input w-full text-center py-1 border-none outline-none bg-transparent"></template>
                        </div>

                        <div class="widget-box flex-1 mb-4 overflow-hidden flex flex-col">
                            <div class="widget-title">ABOUT ME</div>
                            <div class="p-2 flex-1 overflow-y-auto">
                                <template x-if="!editing"><div class="text-[0.8rem] leading-relaxed whitespace-pre-wrap" x-text="form.bio"></div></template>
                                <template x-if="editing"><textarea x-model="form.bio" class="win31-textarea w-full h-full text-[0.8rem] border-none outline-none bg-transparent resize-none"></textarea></template>
                            </div>
                        </div>

                        <div class="widget-box mb-4">
                            <div class="widget-title">SYSTEM INFO</div>
                            <div class="text-[0.6rem] p-1 font-mono uppercase space-y-0.5">
                                <div>USER_ID: <?php echo $user_number; ?></div>
                                <div>THEME: <span x-text="themeColor.toUpperCase()"></span></div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <template x-if="!editing"><button class="win31-btn w-full py-2 font-bold" @click="editing = true">👤 EDIT PROFILE</button></template>
                            <template x-if="editing">
                                <form action="update_profile.php" method="POST" class="flex w-full gap-2 m-0 p-0">
                                    <input type="hidden" name="display_name" :value="form.display_name">
                                    <input type="hidden" name="mood" :value="form.mood">
                                    <input type="hidden" name="bio" :value="form.bio">
                                    <input type="hidden" name="profile_picture_base64" :value="form.pfpPreview">
                                    <input type="file" id="pfpUpload" accept="image/*" class="hidden" @change="handlePfp">
                                    
                                    <button type="submit" class="win31-btn-primary w-full py-2 font-bold">SAVE</button>
                                    <button type="button" class="win31-btn px-4" @click="editing = false; form.pfpPreview = null;">CANCEL</button>
                                </form>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="windows.journal">
                <div class="win31-window absolute z-[6000] w-[600px] h-[450px] left-[200px] top-[120px]">
                    <div class="win31-titlebar" :style="'background:' + themeColor">
                        <span>MY_JOURNAL.EXE</span>
                        <button class="win31-close-btn" @click="windows.journal = false">X</button>
                    </div>
                    <div class="win31-content bg-[#c0c0c0] flex flex-col h-[calc(100%-32px)]">
                        <div class="flex items-center gap-2 p-1 border-b-2 border-black bg-[#c0c0c0]">
                            <button class="win31-btn px-3 py-1 flex items-center gap-1 text-[0.75rem]" @click="journalView = 'list'" x-show="journalView === 'edit'">◀ LIST</button>
                            <button class="win31-btn px-3 py-1 flex items-center gap-1 text-[0.75rem]" @click="journalView = 'edit'" x-show="journalView === 'list'">+ NEW FILE</button>
                            <span class="ml-auto text-[0.65rem] text-gray-600 mr-2 uppercase">C:/JOURNAL/ — <?php echo $journal_count; ?> files</span>
                        </div>
                        <div class="flex flex-1 overflow-hidden">
                            <div class="w-[120px] border-r-2 border-black bg-[#e8e8e8] p-2 text-[0.65rem]">
                                <div class="font-bold border-b border-black mb-2 pb-1">DRIVE C:</div>
                                <div>📁 JOURNAL</div>
                            </div>
                            <div class="flex-1 bg-white overflow-y-auto">
                                <template x-if="journalView === 'list'">
                                    <div class="m-auto text-center p-10 text-gray-400 text-[0.8rem]">No entries yet.</div>
                                </template>
                                <template x-if="journalView === 'edit'">
                                    <div class="h-full flex flex-col p-2">
                                        <input type="text" placeholder="Entry title..." class="win31-input w-full mb-2 font-bold p-2">
                                        <textarea placeholder="Start typing..." class="win31-textarea flex-1 p-2 border-2 border-black resize-none"></textarea>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="windows.gallery">
                <div class="win31-window absolute z-[6000] w-[700px] h-[500px] left-[250px] top-[140px]">
                    <div class="win31-titlebar" :style="'background:' + themeColor">
                        <span>PHOTO_GALLERY.DLL</span>
                        <button class="win31-close-btn" @click="windows.gallery = false">X</button>
                    </div>
                    
                    <div class="win31-content flex flex-col h-[calc(100%-32px)]">
                        <div class="gallery-toolbar">
                            <div class="flex gap-2">
                                <button class="win31-btn flex items-center gap-1 text-[0.7rem]" onclick="document.getElementById('galleryUpload').click()">
                                    📤 UPLOAD
                                </button>
                                <input type="file" id="galleryUpload" class="hidden" accept="image/*">
                            </div>
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
                                        NO PHOTOS IN DIRECTORY.<br>
                                        CLICK UPLOAD TO ADD.
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

                                <div class="strip-item flex items-center justify-center bg-[#e0e0e0] border-2 border-dashed border-gray-500" 
                                     onclick="document.getElementById('galleryUpload').click()">
                                    <span class="text-[1.5rem] text-gray-500">+</span>
                                </div>
                            </div>
                        </div>
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
                        confirmDelete: null,
                        addNote() {
                            if(!this.message.trim()) return;
                            this.notes.push({
                                id: Date.now(),
                                author: '<?php echo addslashes($display_name); ?>',
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
                                    
                                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-3 h-3 bg-red-600 rounded-full border border-black cursor-pointer hover:bg-red-500 shadow-md"
                                         @click="confirmDelete = note.id" title="Remove Note"></div>

                                    <div class="text-[0.6rem] font-bold uppercase border-b border-black/10 mb-2 pb-1">FROM: <span x-text="note.author"></span></div>
                                    <div class="text-[0.75rem] leading-tight" x-text="note.text"></div>
                                </div>
                            </template>
                            <div x-show="notes.length === 0" class="absolute inset-0 flex items-center justify-center text-[#8a6a3a] text-sm opacity-50 italic">Leave a note on the board...</div>
                        </div>

                        <div class="p-3 bg-[#c0c0c0] border-t-2 border-black flex flex-col gap-2">
                            <div class="flex gap-2">
                                <textarea x-model="message" class="win31-textarea flex-1 h-16 p-2 text-[0.8rem]" placeholder="Write something..."></textarea>
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

                        <div x-show="confirmDelete !== null" class="absolute inset-0 flex items-center justify-center bg-black/40 z-[100]" x-cloak>
                            <div class="win31-window w-64">
                                <div class="win31-titlebar bg-red-900"><span>SYSTEM ALERT</span></div>
                                <div class="p-4 bg-[#c0c0c0]">
                                    <p class="text-[0.75rem] mb-4">Are you sure you want to remove this message?</p>
                                    <div class="flex gap-2">
                                        <button @click="notes = notes.filter(n => n.id !== confirmDelete); confirmDelete = null" class="win31-btn-primary bg-red-700 flex-1 py-1">YES</button>
                                        <button @click="confirmDelete = null" class="win31-btn flex-1 py-1">NO</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="windows.customize">
                <div class="win31-window absolute z-[6000] w-[450px] h-[580px] left-[150px] top-[100px]"
                     x-data="{ 
                        tempThemeColor: themeColor,
                        tempTexture: desktopTexture,
                        customColor: themeColor,
                        saved: false,
                        themeColorsList: ['#006400', '#8B0000', '#00008B', '#4B0082', '#556B2F', '#8B4513', '#2F4F4F', '#000080', '#8B008B', '#1a1a1a', '#B8860B', '#5F4F2E'],
                        texturesList: [
                            { id: 'bg-texture-plain', label: 'PLAIN', desc: 'Clean background' },
                            { id: 'bg-texture-grid', label: 'GRID', desc: 'Graph paper style' },
                            { id: 'bg-texture-noise', label: 'NOISE', desc: 'Subtle grain texture' },
                            { id: 'bg-texture-dots', label: 'DOTS', desc: 'Polka dot pattern' }
                        ],
                        applySettings() {
                            themeColor = this.tempThemeColor;
                            desktopTexture = this.tempTexture;
                            this.saved = true;
                            
                            // SAVING TO DATABASE AJAX
                            let formData = new URLSearchParams();
                            formData.append('action', 'save_theme');
                            formData.append('theme_color', themeColor);
                            formData.append('bg_texture', desktopTexture);
                            
                            fetch('dashboard.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: formData.toString()
                            });

                            setTimeout(() => this.saved = false, 2000);
                        }
                     }">
                    <div class="win31-titlebar" :style="'background:' + themeColor">
                        <span>CUSTOMIZE.EXE</span>
                        <button class="win31-close-btn" @click="windows.customize = false">X</button>
                    </div>
                    
                    <div class="win31-content p-4 overflow-y-auto bg-[#f4f4f4] flex flex-col h-[calc(100%-32px)]">
                        
                        <div class="widget-box mb-4">
                            <div class="widget-title flex items-center gap-2">🎨 TITLE BAR COLOR</div>
                            
                            <div class="flex flex-wrap gap-2 mb-3">
                                <template x-for="color in themeColorsList" :key="color">
                                    <div class="color-swatch"
                                         :style="`background: ${color}; outline: ${tempThemeColor === color ? '3px solid #000' : '2px solid #000'}; outline-offset: ${tempThemeColor === color ? '2px' : '0'}; transform: ${tempThemeColor === color ? 'scale(1.15)' : 'scale(1)'}; transition: all 0.15s;`"
                                         @click="tempThemeColor = color; customColor = color"></div>
                                </template>
                            </div>

                            <div class="flex items-center gap-2 mt-2">
                                <label class="text-[0.65rem] font-bold uppercase">Custom:</label>
                                <input type="color" x-model="customColor" @input="tempThemeColor = customColor" class="w-8 h-7 border-2 border-black cursor-pointer p-0">
                                <span class="text-[0.7rem] font-mono" x-text="tempThemeColor"></span>
                            </div>

                            <div class="mt-3">
                                <div class="text-[0.6rem] font-bold uppercase mb-1">Preview:</div>
                                <div :style="`background: ${tempThemeColor}`" class="text-white px-2 py-1 text-[0.7rem] font-bold border-2 border-black flex justify-between font-mono">
                                    <span>YOUR_NAME.EXE</span>
                                    <span>[ - [] X ]</span>
                                </div>
                            </div>
                        </div>

                        <div class="widget-box mb-4">
                            <div class="widget-title flex items-center gap-2">💻 DESKTOP TEXTURE</div>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="t in texturesList" :key="t.id">
                                    <div @click="tempTexture = t.id"
                                         :style="`border: ${tempTexture === t.id ? '3px solid #000' : '2px solid #888'}; background: ${tempTexture === t.id ? '#e0e0e0' : '#f0f0f0'}`"
                                         class="p-2 cursor-pointer transition-all duration-150">
                                        <div class="h-10 mb-1 border border-gray-400" :class="t.id"></div>
                                        <div class="text-[0.65rem] font-bold uppercase" x-text="t.label"></div>
                                        <div class="text-[0.6rem] text-gray-600" x-text="t.desc"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <button class="win31-btn-primary w-full py-2 mt-auto" @click="applySettings">APPLY CHANGES</button>

                        <div x-show="saved" class="mt-2 bg-[#006400] text-white font-mono text-[0.7rem] px-2 py-1 border-2 border-black text-center" x-transition>
                            ✓ Theme applied!
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>

    <script src="js/script.js"></script>
    <script>

        // STRING PHYSICS FOR THE ID CARD LACE
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