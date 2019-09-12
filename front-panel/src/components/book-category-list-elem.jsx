'use strict';

import 'preact';
import {Component} from 'preact';
import Image from 'pimg';

export default class BookCategoryListElem extends Component {
  constructor(props) {
    super(props);

    this.state.open = false;
    this.toggleOpen = this.toggleOpen.bind(this);
  }

  toggleOpen() {
    this.setState({open: !this.state.open});
  }

  render({name, thumbnailSrc, link, childCategories}, {open}, context) {
    const childrenStyle = {
      display: open ? 'block' : 'none',
      paddingLeft: '20px',
    };
    const childCategoryElems =
        Array.isArray(childCategories) && childCategories.length > 0
            ? (
                <div style={childrenStyle}>
                  {childCategories.map((el) =>
                      <BookCategoryListElem name={el.name}
                                            link={el.link}
                                            thumbnailSrc={el.thumbnailSrc}
                                            childCategories={el.childCategories}/>,
                  )}
                </div>
            ) : null;

    return (
        <div>
          <a href={link} style={{lineHeight: '50px', display: 'flex'}}>
            {
              (thumbnailSrc ? <Image class="img" src={thumbnailSrc}
                                     style={{width: '50px', height: '50px'}}/>
                  : <div class="img"/>)
            }
            <span>{name}</span>
          </a>
          {childCategoryElems}
        </div>
    );
  }
}