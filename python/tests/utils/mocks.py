"""Shared mock classes for database connection and activity documents."""
from unittest.mock import AsyncMock, MagicMock
from datetime import datetime
from typing import Any, Dict, Optional


class MockConnection:
    """
    Mock async database connection for Motor-style collections.
    Defaults are safe; test can override return values or the underlying ._coll methods.
    """
    def __init__(
        self,
        collection_name: str = "test",
        find_one_return: Any = None,
        insert_one_return: Any = None,
        update_one_return: Any = None,
        aggregate_return: Any = None,
        find_return: Any = None,
        count_return: int = 0,
    ):
        self._coll = MagicMock(name=f"coll-{collection_name}")
        self._coll.find_one = AsyncMock(return_value=find_one_return)
        self._coll.insert_one = AsyncMock(return_value=insert_one_return)
        if update_one_return is not None:
            self._coll.update_one = AsyncMock(return_value=update_one_return)
        else:
            self._coll.update_one = AsyncMock(return_value=MagicMock(matched_count=1, modified_count=1))
        if aggregate_return is not None:
            agg_cur = MagicMock()
            agg_cur.to_list = AsyncMock(return_value=aggregate_return)
            self._coll.aggregate = MagicMock(return_value=agg_cur)
        else:
            self._coll.aggregate = MagicMock(return_value=MagicMock(to_list=AsyncMock(return_value=[])))
        if find_return is not None:
            find_cur = MagicMock()
            find_cur.to_list = AsyncMock(return_value=find_return)
            find_cur.sort = MagicMock(return_value=find_cur)
            find_cur.skip = MagicMock(return_value=find_cur)
            find_cur.limit = MagicMock(return_value=find_cur)
            self._coll.find = MagicMock(return_value=find_cur)
        else:
            find_cur = MagicMock()
            find_cur.to_list = AsyncMock(return_value=[])
            find_cur.sort = MagicMock(return_value=find_cur)
            find_cur.skip = MagicMock(return_value=find_cur)
            find_cur.limit = MagicMock(return_value=find_cur)
            self._coll.find = MagicMock(return_value=find_cur)
        self._coll.count_documents = AsyncMock(return_value=count_return)

    async def close(self):
        pass

    def __getitem__(self, key):
        return self._coll


def make_activity_doc(
    activity_id: str,
    user_id: str,
    activity_type: Any,
    description: str,
    timestamp: datetime,
    **kwargs
) -> Dict[str, Any]:
    """
    Create a plain dict representing an activity document.
    """
    act_type = activity_type.value if hasattr(activity_type, "value") else activity_type
    doc: Dict[str, Any] = {
        "_id": activity_id,
        "user_id": user_id,
        "activity_type": act_type,
        "description": description,
        "timestamp": timestamp,
    }
    doc.update(kwargs)
    return doc
