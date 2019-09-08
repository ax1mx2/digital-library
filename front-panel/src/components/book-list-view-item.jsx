'use strict';

import Image from 'pimg';

export default function BookListViewItem({title, authors, excerpt, img, link}) {
  return (
      <div>
        <a href={link} title={title}>
          <Image src={img} style={{height: '400px', width: '100%'}}/>
          <h4 class="fancy_heading">{title}</h4>
        </a>
      </div>
  );
}