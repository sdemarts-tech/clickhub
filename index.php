<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Earn Points Playing Games</title>
    <?php include 'includes/header-links.php'; ?>
</head>
<body>

      <div id="container">
        
        
          <div class="logo"><img src="assets/logo.png" alt="" ></div>
        <!-- Hero Section -->
        <div class="hero-container">
          
            <!-- Left Content Side -->
            <div class="hero-content">
                <h1 class="hero-headline">
                    Play. Earn.<br>Repeat.
                </h1>
                
                <p class="hero-subheading">
                    Turn your free time into real rewards on ClickHub — the gamified platform where fun meets extra income.
                </p>
                
                <a href="#signup" class="cta-button btn">
                    Start Earning Now
                </a>
              
              <div class="home-login"><a href="login.php" class="btn">Login to you account</a></div>
                
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon game-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="feature-text">
                            <strong>Play fun HTML5 games</strong> anytime, anywhere
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon cash-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="feature-text">
                            <strong>Convert your points</strong> to real cash or rewards
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon friends-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="feature-text">
                            <strong>Earn more</strong> by inviting friends
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon device-icon">
                            <i class="fas fa-laptop"></i>
                        </div>
                        <div class="feature-text">
                            <strong>Works on any</strong> phone or laptop
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Mockup Side -->
            <div class="hero-mockup">
                <div class="mockup-card">
                    <!-- Top card -->
                    <div class="ui-card small-card">
                        <div class="card-icon play-icon">
                            <i class="fas fa-play"></i>
                        </div>
                        <div class="card-bar"></div>
                        <div class="card-bar short"></div>
                    </div>
                    
                    <!-- Second card -->
                    <div class="ui-card small-card">
                        <div class="card-icon check-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="card-bar"></div>
                        <div class="card-bar short"></div>
                    </div>
                    
                    <!-- Center large card (main feature) -->
                    <div class="ui-card large-card">
                        <div class="large-card-icon">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                    
                    <!-- Bottom card -->
                    <div class="ui-card small-card">
                        <div class="card-icon minus-icon">
                            <i class="fas fa-minus"></i>
                        </div>
                        <div class="card-bar"></div>
                        <div class="card-bar short"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- How It Works Section -->
        <section class="how-it-works">
            <h2 class="section-title">HOW IT WORKS</h2>
            
            <div class="steps-container">
                <div class="step">
                  <div class="step-number"><span>1</span></div>
                    <div class="step-content">
                        <h3>Sign Up Free</h3>
                        <p>Create your ClickHub account in less than a minute. No credit card needed.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number"><span>2</span></div>
                    <div class="step-content">
                        <h3>Play & Earn</h3>
                        <p>Choose from mini games, clicker tasks, or daily activities to start collecting points.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number"><span>3</span></div>
                    <div class="step-content">
                        <h3>Level Up with Referrals</h3>
                        <p>Invite your friends — you'll earn a percentage of their activity too!</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number"><span>4</span></div>
                    <div class="step-content">
                        <h3>Cash Out</h3>
                        <p>Convert your points to real money through GCash, PayPal, or bank transfer.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Features Section -->
        <section class="features-section">
            <div class="feature-card">
                <div class="feature-icon-large">
                    <i class="fas fa-gamepad"></i>
                </div>
              
              	<div class="feature-text">
                <h3>Play to Earn</h3>
                <p>Enjoy quick, easy-to-play HTM5 games like Color Dice, Snake, and Tap Clicker — fun to play, rewarding to win.</p>
              </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon-large">
                    <i class="fas fa-lightbulb"></i>
                </div>
              <div class="feature-text">
                <h3>Smart Tasks</h3>
                <p>Earn by solving captchas, watching short clips, or staying active in time-based games.</p>
              </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon-large">
                    <i class="fas fa-users"></i>
                </div>
              
              <div class="feature-text">
                <h3>Referral Rewards</h3>
                <p>Share your link — get commissions for every active friend who signs up under you.</p>
                
              </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon-large">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
              <div class="feature-text">
                <h3>Real Cash Conversions</h3>
                <p>Every point you earn has real value. Withdraw safely through GCash, PayPal, or bank.</p>
              </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon-large">
                    <i class="fas fa-calendar-alt"></i>
                </div>
              <div class="feature-text">
                <h3>Daily Earning Limits</h3>
                <p>No spam. No bots. ClickHub ensures fair play so legitimate users get rewarded.</p>
              </div>
            </div>
        </section>
        
        <!-- Games Showcase -->
        <section class="games-showcase">
            <div class="showcase-header">
                <i class="fas fa-gamepad"></i>
                <h2>GAMES SHOWCASE</h2>
            </div>
            
            <h3 class="showcase-headline">Play, relax, and earn — all in one hub.</h3>
            <p class="showcase-subtext">ClickHub offers a growing library of casual browser games you can play instantly:</p>
            
            <div class="games-grid">
                <div class="game-card">
                    <div class="game-icon color-game">
                        <i class="fas fa-dice"></i>
                    </div>
                    <h4>Color Game Dice</h4>
                    <p>Bet on colors, roll the dice, and multiply your points.</p>
                </div>
                
                <div class="game-card">
                    <div class="game-icon snake-game">
                        <i class="fas fa-snake"></i>
                    </div>
                    <h4>Snake Classic</h4>
                    <p>Survive longer, earn more points per minute.</p>
                </div>
                
                <div class="game-card">
                    <div class="game-icon tap-game">
                        <i class="fas fa-hand-pointer"></i>
                    </div>
                    <h4>Tap Clicker</h4>
                    <p>Test your speed and see your earnings grow with every click.</p>
                </div>
            </div>
            
            <p class="showcase-footer">No downloads, no installs — just instant fun and instant rewards.</p>
        </section>
        
        <!-- Trust Section -->
        <section class="trust-section">
            <div class="trust-header">
                <i class="fas fa-lock"></i>
                <h2>TRUST & LEGITIMACY</h2>
            </div>
            
            <h3 class="trust-headline">Real Rewards. Real People.</h3>
            
            <p class="trust-text">Thousands of users are already playing and earning from their browsers.</p>
            <p class="trust-text">ClickHub is built for transparency, with daily limits and admin checks to prevent abuse.</p>
            
            <div class="testimonials">
                <div class="testimonial">
                    <i class="fas fa-comment"></i>
                    <p>"Finally, an earning app that's actually fun."</p>
                </div>
                
                <div class="testimonial">
                    <i class="fas fa-comment"></i>
                    <p>"Legit! I got my first payout in GCash within a day."</p>
                </div>
            </div>
            
            <p class="trust-footer">Secure. Transparent. Always free to join.</p>
        </section>
        
        <!-- Referral Program -->
        <section class="referral-section">
            <div class="referral-header">
                <i class="fas fa-handshake"></i>
                <h2>REFERRAL PROGRAM</h2>
            </div>
            
            <h3 class="referral-headline">Your friends can make you richer — literally.</h3>
            
            <p class="referral-text">When someone joins ClickHub through your referral link, you earn a commission from their points — automatically.</p>
            
            <div class="referral-benefits">
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <p>Earn a percentage of your referrals' activities</p>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <p>See your network grow in your dashboard</p>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <p>Invite via Facebook, Messenger, or direct link</p>
                </div>
            </div>
            
            <p class="referral-footer">Your link. Your community. Your passive income.</p>
            
            <a href="#signup" class="referral-cta btn">
                <i class="fas fa-rocket"></i> Register Now
            </a>
        </section>
        
        <!-- FAQ Teaser -->
        <section class="faq-section">
            <div class="faq-header">
                <i class="fas fa-comment-dots"></i>
                <h2>FAQ TEASER</h2>
            </div>
            
            <div class="faq-grid">
                <div class="faq-item">
                    <h4>Is ClickHub legit?</h4>
                    <p>Yes. ClickHub is a transparent, bbrowser-based platform — not an app scam or crypto trap. You earn points for real actions and can withdraw through trusted channels.</p>
                </div>
                
                <div class="faq-item">
                    <h4>How do I get paid?</h4>
                    <p>Withdraw your points to GCash, PayPal, or your bank account — fast and secure.</p>
                </div>
                
                <div class="faq-item">
                    <h4>Do I need to pay anything?</h4>
                    <p>No. ClickHub is 100% free to join and play.</p>
                </div>
                
                <div class="faq-item">
                    <h4>How often can I earn?</h4>
                    <p>Every day! You can play games, complete tasks, and refer friends daily — with fair earning limits to keep it sustainable.</p>
                </div>
            </div>
        </section>
        
        <!-- Final CTA -->
        <section class="final-cta">
            <div class="cta-icon">
                <i class="fas fa-mouse-pointer"></i>
            </div>
            
            <h2>Join ClickHub today — start earning while having fun.</h2>
            
            <p>Whether you're killing time or chasing side income, your clicks can finally pay off.</p>
            
            <div class="cta-features">
                <span><i class="fas fa-star"></i> Free to join. No downloads. Mobile-ready.</span>
            </div>
            
            <a href="signup.php" class="final-cta-button btn">
                <i class="fas fa-fire"></i> Start Earning Now!
            </a>
        </section>
    </div>

   <?php include 'includes/footer-links.php'; ?>
</body>
</html>