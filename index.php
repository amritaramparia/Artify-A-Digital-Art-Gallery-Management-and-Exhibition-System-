<?php
  session_start();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artify - Discover Unique Artworks</title>
    <link rel="stylesheet" href="styles.css?v=1.1">
</head>
<body>
  <?php include('header.php');?>
  
  <!-- Hero Section - Updated to match reference image layout -->
  <section class="hero">
    <div class="hero-content">
        <h1>Discover, Buy, and Auction Unique Artworks</h1>
        <p>Connecting artists and art lovers worldwide. Bid on exclusive artworks or sell your pieces in our curated auctions. No hidden fees.</p>
    </div>
    <div class="hero-image"></div>
  </section>

  <!-- Featured Artworks - Updated with clickable cards -->
<section class="featured">
    <h2 class="section-title">Featured Artworks</h2>
    <div class="art-grid">
      <?php
      // Database connection
      $servername = "localhost";
      $username = "root";
      $password = "";
      $dbname = "art";

      // Create connection
      $conn = new mysqli($servername, $username, $password, $dbname);

      // Check connection
      if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
      }

      // Get 4 most recent artworks
      $sql = "SELECT a.*, ar.artist_name FROM artworks a 
              JOIN artists ar ON a.artist_id = ar.artist_id 
              ORDER BY a.created_at DESC LIMIT 4";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
          while($artwork = $result->fetch_assoc()) {
              $title = htmlspecialchars($artwork['title']);
              $price = number_format($artwork['price'], 2);
              $artist = htmlspecialchars($artwork['artist_name']);
              $artwork_id = $artwork['artwork_id'];
              $image_url = $artwork['image_url'];
              
              // Handle image display
              if (!empty($image_url) && file_exists($image_url)) {
                  $imageDisplay = "<img src='{$image_url}' alt='{$title}' class='art-image'>";
              } else {
                  // Fallback to placeholder
                  $imageDisplay = "<div class='art-image-placeholder'><span>{$title}</span></div>";
              }
              
              echo "
              <a href='artwork-details.php?id={$artwork_id}' class='art-card-link'>
                <div class='art-card'>
                  {$imageDisplay}
                  <div class='art-details'>
                    <h3>{$title}</h3>
                    <p>by {$artist}</p>
                    <p class='price'>Rs. {$price}</p>
                  </div>
                </div>
              </a>";
          }
      } 
      $conn->close();
      ?>
    </div>
    <div class="view-more-container">
      <a href="artgallary.php" class="view-more-button">More Explorer →</a>
    </div>
</section>

  <!-- Featured Artists - Updated with clickable cards -->
<section class="featured-artists">
    <h2 class="section-title">Our Featured Artists</h2>
    <div class="artist-grid">
      <?php
      // Create connection again
      $conn = new mysqli($servername, $username, $password, $dbname);
      
      // Get 4 popular approved artists
      $sql = "SELECT a.* FROM artists a 
              WHERE a.approval_status = 'approved' 
              ORDER BY a.artist_id DESC 
              LIMIT 4";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
          while($artist = $result->fetch_assoc()) {
              $artist_name = htmlspecialchars($artist['artist_name']);
              $art_style = htmlspecialchars($artist['art_style']);
              $artist_id = $artist['artist_id'];
              $profile_pic = $artist['profile_pic'];
              
              // Handle profile picture display
              if (!empty($profile_pic) && file_exists($profile_pic)) {
                  $profileDisplay = "<img src='{$profile_pic}' alt='{$artist_name}' class='artist-image'>";
              } else {
                  $profileDisplay = "<div class='artist-image-placeholder'></div>";
              }
              
              echo "
              <a href='artist-details.php?id={$artist_id}' class='artist-card-link'>
                <div class='artist-card'>
                  {$profileDisplay}
                  <h3>{$artist_name}</h3>
                  <span class='view-gallery'>VIEW GALLERY</span>
                </div>
              </a>";
          }
      } else {
          // Fallback to static content if no artists found
          echo "
          <a href='artist-details.php?id=1' class='artist-card-link'>
            <div class='artist-card'>
              <img src='https://www.singulart.com/blog/wp-content/uploads/2023/08/image-35-719x1024.png' alt='Taranjeet Kaur' class='artist-image'>
              <h3>Taranjeet Kaur</h3>
              <span class='view-gallery'>VIEW GALLERY</span>
            </div>
          </a>
          <a href='artist-details.php?id=2' class='artist-card-link'>
            <div class='artist-card'>
              <img src='https://i.pinimg.com/originals/f2/ff/77/f2ff77b2a925fe7825359ae6c3ac8081.jpg' alt='Arpita Biswas' class='artist-image'>
              <h3>Arpita Biswas</h3>
              <span class='view-gallery'>VIEW GALLERY</span>
            </div>
          </a>
          <a href='artist-details.php?id=3' class='artist-card-link'>
            <div class='artist-card'>
              <img src='https://media.tate.org.uk/aztate-prd-ew-dg-wgtail-st1-ctr-data/images/dsc06674_2_ALTPs5S.width-600.jpg' alt='Ananya Roy' class='artist-image'>
              <h3>Ananya Roy</h3>
              <span class='view-gallery'>VIEW GALLERY</span>
            </div>
          </a>
          <a href='artist-details.php?id=4' class='artist-card-link'>
            <div class='artist-card'>
              <img src='https://i.pinimg.com/originals/e8/ec/d6/e8ecd66c7cf8b0b0df5e74866309a65f.jpg' alt='Ulka & Kiah' class='artist-image'>
              <h3>Ulka & Kiah</h3>
              <span class='view-gallery'>VIEW GALLERY</span>
            </div>
          </a>";
      }
      $conn->close();
      ?>
    </div>
</section>

  <!-- Sell Your Art Section -->
  <section class="sell-art">
    <div class="sell-content">
      <h2>Sell Your Paintings Online</h2>
      <p>Artify is an online platform for promoting quality art created by artists worldwide. With a simple registration process, we allow you to sell paintings as many as you choose, with the freedom of putting up the price you want.</p>
      <a href="#" class="cta-button">Know More</a>
    </div>
  </section>

  <!-- Testimonial Section -->
  <section class="testimonial">
    <div class="testimonial-content">
      <blockquote>
        "Artify has given me my own space to show my artworks to the entire world. I have all my artwork at one place online. A single button social share is too good; I keep sharing my painting desk on my social accounts."
        <cite>- A.B. Kaser, Artist</cite>
      </blockquote>
    </div>
  </section>
  
  <?php include('footer.html');?>
</body>
</html>