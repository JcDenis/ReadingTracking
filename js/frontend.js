/*global jQuery, dotclear, jsToolBar */
'use strict';

dotclear.getReadingTrackingArtifact = (post) => {
  try {
    const request = new XMLHttpRequest(),
        requestUrl = dotclear.ReadingTracking.url + post.id.substr(1),
        target = post.querySelector('.post-title');

    if (target) {
      request.onreadystatechange = function() {
        if ( request.readyState === 4 && request.status === 200 ) {
          const response = JSON.parse( request.responseText );

          target.prepend(response.ret)
        }
      };

      request.open('GET', requestUrl);
      request.responseType = 'text';
      request.send();
    }
  } catch ( e ) {
  }
};

dotclear.ready(() => {
  const ReadingTracking = dotclear.getData('ReadingTracking');
  dotclear.ReadingTracking = ReadingTracking;

  const rtPosts = document.querySelectorAll('article');
  if (rtPosts) {
    rtPosts.forEach(post => {
      dotclear.getReadingTrackingArtifact(post);
    });
  }
});
