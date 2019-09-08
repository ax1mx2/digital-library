'use strict';

import 'preact';
import {Component} from 'preact';

export default class BookCategoryListElem extends Component {
  constructor(props) {
    super(props);

    this.state.open = false;
    this.toggleOpen = this.toggleOpen.bind(this);
  }

  toggleOpen() {
    this.setState({open: !this.state.open});
  }

  render({name, childCategories}, {open}, context) {
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
                                            childCategories={el.childCategories}/>,
                  )}
                </div>
            ) : null;

    return (
        <div>
          <span style={{cursor: 'pointer'}}
                onClick={this.toggleOpen}>{name}</span>
          {childCategoryElems}
        </div>
    );
  }
}