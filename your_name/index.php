<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YOUR NAME | v1.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400&family=IM+Fell+English:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="yn-body w-screen h-screen overflow-hidden flex items-center" 
      x-data="{ 
        authMode: null, 
        titleShifted: false, 
        forgotMode: false,
        lettersVisible: false,
        signupData: { display_name: '', email: '', password: '', confirm_password: '' },
        signinData: { email: '', password: '' }
      }"
      x-init="setTimeout(() => lettersVisible = true, 100)">

    <div class="absolute inset-0 opacity-30 pointer-events-none bg-texture-dots"></div>

    <div class="relative w-full h-full flex">
        <div class="flex-1 flex items-center justify-center min-w-0">
            <div class="flex flex-col items-center transition-all duration-700 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                 :class="titleShifted ? 'translate-x-0 scale-[0.78]' : 'translate-x-0 scale-100'">
                
                <h1 class="select-none leading-none font-[900] text-[#000] tracking-tighter"
                    style="font-family: 'Georgia', 'IM Fell English', serif; font-size: clamp(3rem, 9vw, 8rem);">
                    <template x-for="(letter, i) in ['y','o','u','r',' ','n','a','m','e']">
                        <span class="yn-letter" 
                              x-text="letter"
                              :style="'animation-delay: ' + (i * 80) + 'ms; display: ' + (letter === ' ' ? 'inline' : 'inline-block') + (letter === ' ' ? '; width: 0.35em' : '')">
                        </span>
                    </template>
                </h1>

                <p class="mt-4 mb-8 text-center uppercase tracking-[0.19em] text-[#444] transition-opacity duration-700 delay-[900ms]"
                   :class="lettersVisible ? 'opacity-100' : 'opacity-0'"
                   style="font-family: 'Courier Prime', monospace; font-size: clamp(0.65rem, 1.8vw, 0.95rem);">
                    every name deserves its own page
                </p>

                <div class="flex gap-4 transition-opacity duration-700 delay-[1100ms]"
                     :class="lettersVisible ? 'opacity-100' : 'opacity-0'">
                    <button class="win31-btn-primary px-[28px] py-[10px]" 
                            @click="authMode = 'signup'; titleShifted = true; forgotMode = false"
                            :style="authMode === 'signup' ? 'background: #004400; box-shadow: 1px 1px 0 #000; transform: translate(2px,2px)' : ''">
                        CLAIM YOUR SPACE
                    </button>
                    <button class="win31-btn px-[28px] py-[10px]" 
                            @click="authMode = 'signin'; titleShifted = true; forgotMode = false"
                            :style="authMode === 'signin' ? 'background: #ddd; box-shadow: inset -1px -1px #fff, inset 1px 1px #808080' : ''">
                        COME BACK
                    </button>
                </div>

                <div class="mt-10 flex items-center gap-3 transition-opacity duration-700 delay-[1300ms]"
                     :class="lettersVisible ? 'opacity-100' : 'opacity-0'">
                </div>
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center min-w-0" 
             :class="authMode ? 'pointer-events-auto' : 'pointer-events-none'">
            
            <template x-if="authMode">
                <div class="yn-auth-window w-[min(420px,90%)] max-h-[90vh] overflow-y-auto" x-cloak>
                    <div class="win31-window shadow-[8px_8px_0_#000] w-full">
                        <div class="win31-titlebar bg-[#006400] p-[7px_10px]">
                            <span class="text-[0.85rem]" x-text="authMode === 'signup' ? 'REGISTER.EXE' : 'LOGIN.EXE'"></span>
                            <button class="win31-close-btn" @click="authMode = null; titleShifted = false">X</button>
                        </div>

                        <div class="p-[22px_20px] bg-[#c0c0c0]">
                            
                            <form x-show="authMode === 'signup'" action="register.php" method="POST" @submit="if(signupData.password !== signupData.confirm_password) { alert('Passwords do not match'); $event.preventDefault(); }">
                                <div class="space-y-4 text-left">
                                    <div>
                                        <label class="win31-label block font-bold text-[0.7rem] mb-1 uppercase">Display Name *</label>
                                        <input class="win31-input" type="text" name="display_name" x-model="signupData.display_name" placeholder="your name here" required>
                                    </div>
                                    <div>
                                        <label class="win31-label block font-bold text-[0.7rem] mb-1 uppercase">Email *</label>
                                        <input class="win31-input" type="email" name="email" x-model="signupData.email" placeholder="you@gmail.com" required>
                                        <div class="font-mono text-[0.6rem] text-[#555] mt-1">e.g. name@gmail.com, name@yahoo.com</div>
                                    </div>
                                    <div>
                                        <label class="win31-label block font-bold text-[0.7rem] mb-1 uppercase">Password *</label>
                                        <input class="win31-input" type="password" name="password" x-model="signupData.password" placeholder="••••••••" required>
                                        <div class="mt-2 text-[0.6rem] space-y-1 font-mono">
                                            <div :class="signupData.password.length >= 8 ? 'text-[#006400]' : 'text-gray-500'">
                                                <span x-text="signupData.password.length >= 8 ? '✓' : '○'"></span> 8+ characters
                                            </div>
                                            <div :class="/[A-Z]/.test(signupData.password) ? 'text-[#006400]' : 'text-gray-500'">
                                                <span x-text="/[A-Z]/.test(signupData.password) ? '✓' : '○'"></span> Uppercase letter
                                            </div>
                                            <div :class="/[0-9]/.test(signupData.password) ? 'text-[#006400]' : 'text-gray-500'">
                                                <span x-text="/[0-9]/.test(signupData.password) ? '✓' : '○'"></span> Number
                                            </div>
                                            <div :class="/[^a-zA-Z0-9]/.test(signupData.password) ? 'text-[#006400]' : 'text-gray-500'">
                                                <span x-text="/[^a-zA-Z0-9]/.test(signupData.password) ? '✓' : '○'"></span> Special character
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="win31-label block font-bold text-[0.7rem] mb-1 uppercase">Confirm Password *</label>
                                        <input class="win31-input" type="password" name="confirm_password" x-model="signupData.confirm_password" placeholder="••••••••" required
                                               :style="signupData.confirm_password && signupData.confirm_password !== signupData.password ? 'border-color: #cc0000' : ''">
                                        <div x-show="signupData.confirm_password && signupData.confirm_password !== signupData.password" class="text-[0.6rem] text-[#cc0000] mt-1 font-mono">Passwords don't match</div>
                                    </div>
                                    <button type="submit" class="win31-btn-primary w-full p-2 text-[0.85rem]">MAKE YOUR NAME →</button>
                                </div>
                            </form>

                            <form x-show="authMode === 'signin' && !forgotMode" action="login.php" method="POST">
                                <div class="space-y-4 text-left">
                                    <div>
                                        <label class="win31-label block font-bold text-[0.7rem] mb-1 uppercase">Email</label>
                                        <input class="win31-input" type="email" name="email" placeholder="you@email.com" required>
                                    </div>
                                    <div>
                                        <label class="win31-label block font-bold text-[0.7rem] mb-1 uppercase">Password</label>
                                        <input class="win31-input" type="password" name="password" placeholder="••••••••" required>
                                    </div>
                                    <div class="text-right">
                                        <button type="button" @click="forgotMode = true" class="text-[0.62rem] text-[#006400] underline font-mono">Forgot password?</button>
                                    </div>
                                    <button type="submit" class="win31-btn-primary w-full p-2 text-[0.85rem]">RETURN HOME →</button>
                                </div>
                            </form>

                            <div x-show="forgotMode" x-cloak>
                                <h2 class="font-bold text-[0.75rem] border-b-2 border-black pb-2 mb-3 font-mono">FORGOT PASSWORD</h2>
                                <p class="text-[0.7rem] text-[#444] mb-4 font-mono leading-relaxed">Since this is a local demo, passwords are stored in your database via phpMyAdmin. Try checking your display name or re-registering with the same email.</p>
                                <button type="button" @click="forgotMode = false" class="win31-btn w-full p-2 text-[0.75rem]">← BACK TO LOGIN</button>
                            </div>

                            <div class="mt-4 text-center" x-show="!forgotMode">
                                <button type="button" @click="authMode = (authMode === 'signup' ? 'signin' : 'signup')" class="text-[0.68rem] underline font-mono text-[#444]">
                                    <span x-text="authMode === 'signup' ? 'Already have a name? → Come back' : 'New here? → Claim your space'"></span>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="absolute bottom-6 right-8 font-mono text-[1rem] text-[#999] tracking-[0.1em] transition-opacity duration-1000 delay-[1500ms]"
         :class="lettersVisible ? 'opacity-100' : 'opacity-0'">
         &copy; YOUR_NAME PLATFORM <?php echo date("Y"); ?>
    </div>

</body>
</html>