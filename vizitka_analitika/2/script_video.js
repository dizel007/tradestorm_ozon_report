

  const video = document.getElementById('myVideo');

  // Функция запроса полноэкранного режима для элемента
  function requestFullscreen(element) {
    if (element.requestFullscreen) {
      element.requestFullscreen();
    } else if (element.mozRequestFullScreen) {
      element.mozRequestFullScreen();
    } else if (element.webkitRequestFullscreen) {
      element.webkitRequestFullscreen();
    } else if (element.msRequestFullscreen) {
      element.msRequestFullscreen();
    }
  }

  // При клике на видео
  video.addEventListener('click', function() {
    // Сначала пробуем перейти в полноэкранный режим
    requestFullscreen(video);
    // Затем запускаем видео (если оно ещё не играет)
    video.play();
  });

  // При окончании видео можно автоматически выйти из полноэкранного режима (опционально)
  video.addEventListener('ended', function() {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    } else if (document.mozCancelFullScreen) {
      document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
      document.msExitFullscreen();
    }
  });
