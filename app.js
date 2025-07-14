function getLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function(position) {
        alert("Latitude: " + position.coords.latitude + "\nLongitude: " + position.coords.longitude);
        // Here, you would send the coordinates to your backend to fetch nearby saloons
      },
      function(error) {
        alert("Unable to retrieve your location.");
      }
    );
  } else {
    alert("Geolocation is not supported by this browser.");
  }
}