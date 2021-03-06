'use strict';

import Image from 'pimg';

export default function BookListViewItem({title, img, link}) {
  return (
      <div>
        <a href={link} title={title}>
          <Image src={img} style={{width: '100%'}}/>
        </a>
      </div>
  );
}